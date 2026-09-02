<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "worker") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$message_type = "error";

/*
|--------------------------------------------------------------------------
| GET WORKER
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($conn, "
    SELECT
        worker_profiles.*,
        users.name,
        users.email,
        users.phone,
        users.avatar
    FROM worker_profiles
    JOIN users ON worker_profiles.user_id = users.id
    WHERE worker_profiles.user_id = ?
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: create_worker_profile.php");
    exit();
}

$worker = mysqli_fetch_assoc($result);
$worker_profile_id = (int)$worker['id'];


/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];

$categoryResult = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name ASC"
);

while ($cat = mysqli_fetch_assoc($categoryResult)) {
    $categories[] = $cat;
}


/*
|--------------------------------------------------------------------------
| LOAD SELECTED PROFESSIONS
|--------------------------------------------------------------------------
*/

$selectedCategoryIds = [];

$catStmt = mysqli_prepare($conn, "
    SELECT category_id
    FROM worker_categories
    WHERE worker_profile_id = ?
");

mysqli_stmt_bind_param(
    $catStmt,
    "i",
    $worker_profile_id
);

mysqli_stmt_execute($catStmt);

$catResult = mysqli_stmt_get_result($catStmt);

while ($row = mysqli_fetch_assoc($catResult)) {
    $selectedCategoryIds[] = (int)$row['category_id'];
}

mysqli_stmt_close($catStmt);


/*
|--------------------------------------------------------------------------
| PROFILE PHOTO
|--------------------------------------------------------------------------
*/

$uploadDir = "../uploads/profile/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if (isset($_POST['update_profile'])) {

    $selectedCategories = $_POST['categories'] ?? [];

    $experience = intval($_POST['experience'] ?? 0);
    $daily_rate = floatval($_POST['daily_rate'] ?? 0);
    $address = trim($_POST['address'] ?? "");
    $bio = trim($_POST['bio'] ?? "");
    $availability = $_POST['availability'] ?? "Available";

    /*
    |--------------------------------------------------------------------------
    | CLEAN CATEGORY IDS
    |--------------------------------------------------------------------------
    */

    $selectedCategories = array_map(
        'intval',
        $selectedCategories
    );

    $selectedCategories = array_unique(
        array_filter($selectedCategories)
    );

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (count($selectedCategories) === 0) {

        $message = "Please select at least one profession.";

    } elseif (empty($address)) {

        $message = "Please enter your address.";

    } elseif (empty($bio)) {

        $message = "Please tell clients something about your work.";

    } elseif ($experience < 0) {

        $message = "Experience cannot be negative.";

    } elseif ($daily_rate <= 0) {

        $message = "Daily rate must be greater than zero.";

    } elseif (!in_array($availability, ['Available', 'Busy'], true)) {

        $message = "Invalid availability.";

    } else {

        mysqli_begin_transaction($conn);

        try {

            /*
            |--------------------------------------------------------------------------
            | PROFILE PHOTO
            |--------------------------------------------------------------------------
            */

            $newAvatar = $worker['avatar'];

            if (
                isset($_FILES['profile_photo']) &&
                $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("There was a problem uploading the photo.");
                }

                $file = $_FILES['profile_photo'];

                /*
                | Maximum 5MB
                */

                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new Exception("Profile photo must be smaller than 5MB.");
                }

                /*
                | Check actual image type
                */

                $imageInfo = getimagesize($file['tmp_name']);

                if ($imageInfo === false) {
                    throw new Exception("Please upload a valid image.");
                }

                $allowedTypes = [
                    IMAGETYPE_JPEG => 'jpg',
                    IMAGETYPE_PNG  => 'png',
                    IMAGETYPE_WEBP => 'webp'
                ];

                $imageType = $imageInfo[2];

                if (!isset($allowedTypes[$imageType])) {
                    throw new Exception(
                        "Only JPG, PNG and WEBP images are allowed."
                    );
                }

                $extension = $allowedTypes[$imageType];

                /*
                | Unique filename
                */

                $filename =
                    "worker_" .
                    $user_id .
                    "_" .
                    time() .
                    "." .
                    $extension;

                $destination = $uploadDir . $filename;

                if (!move_uploaded_file(
                    $file['tmp_name'],
                    $destination
                )) {
                    throw new Exception(
                        "Could not save the profile photo."
                    );
                }

                /*
                | Delete previous uploaded photo
                */

                if (
                    !empty($worker['avatar']) &&
                    strpos($worker['avatar'], 'uploads/profile/') === 0
                ) {

                    $oldFile = "../" . $worker['avatar'];

                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $newAvatar = "uploads/profile/" . $filename;
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE WORKER PROFILE
            |--------------------------------------------------------------------------
            */

            $update = mysqli_prepare($conn, "
                UPDATE worker_profiles
                SET
                    experience = ?,
                    daily_rate = ?,
                    address = ?,
                    bio = ?,
                    availability = ?
                WHERE user_id = ?
            ");

            mysqli_stmt_bind_param(
                $update,
                "idsssi",
                $experience,
                $daily_rate,
                $address,
                $bio,
                $availability,
                $user_id
            );

            if (!mysqli_stmt_execute($update)) {
                throw new Exception(
                    "Could not update worker profile."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE PHOTO
            |--------------------------------------------------------------------------
            */

            $avatarUpdate = mysqli_prepare($conn, "
                UPDATE users
                SET avatar = ?
                WHERE id = ?
            ");

            mysqli_stmt_bind_param(
                $avatarUpdate,
                "si",
                $newAvatar,
                $user_id
            );

            if (!mysqli_stmt_execute($avatarUpdate)) {
                throw new Exception(
                    "Could not update profile photo."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | REPLACE PROFESSIONS
            |--------------------------------------------------------------------------
            */

            $deleteCategories = mysqli_prepare($conn, "
                DELETE FROM worker_categories
                WHERE worker_profile_id = ?
            ");

            mysqli_stmt_bind_param(
                $deleteCategories,
                "i",
                $worker_profile_id
            );

            if (!mysqli_stmt_execute($deleteCategories)) {
                throw new Exception(
                    "Could not update professions."
                );
            }


            $insertCategory = mysqli_prepare($conn, "
                INSERT INTO worker_categories
                    (worker_profile_id, category_id)
                VALUES
                    (?, ?)
            ");

            foreach ($selectedCategories as $category_id) {

                mysqli_stmt_bind_param(
                    $insertCategory,
                    "ii",
                    $worker_profile_id,
                    $category_id
                );

                if (!mysqli_stmt_execute($insertCategory)) {
                    throw new Exception(
                        "Could not save profession."
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            mysqli_commit($conn);

            $_SESSION['success'] =
                "Your profile has been updated successfully.";

            header("Location: my_profile.php");
            exit();

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $message = $e->getMessage();

            /*
            | Remove newly uploaded file if transaction failed
            */

            if (
                isset($newAvatar) &&
                $newAvatar !== $worker['avatar'] &&
                strpos($newAvatar, 'uploads/profile/') === 0
            ) {

                $newFile = "../" . $newAvatar;

                if (file_exists($newFile)) {
                    unlink($newFile);
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | KEEP VALUES
    |--------------------------------------------------------------------------
    */

    $selectedCategoryIds = $selectedCategories;

    $worker['experience'] = $experience;
    $worker['daily_rate'] = $daily_rate;
    $worker['address'] = $address;
    $worker['bio'] = $bio;
    $worker['availability'] = $availability;
}


/*
|--------------------------------------------------------------------------
| PROFILE IMAGE URL
|--------------------------------------------------------------------------
*/

$profileImage = "";

if (
    !empty($worker['avatar']) &&
    strpos($worker['avatar'], 'uploads/profile/') === 0
) {
    $profileImage = "../" . $worker['avatar'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Profile | BlueCollar-Hire</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
    >

    <style>

        .edit-profile-page {
            padding: 40px 20px 60px;
        }

        .edit-profile-container {
            max-width: 850px;
            margin: auto;
        }

        .edit-header {
            margin-bottom: 25px;
        }

        .edit-header h1 {
            margin-bottom: 5px;
        }

        .edit-header p {
            color: #64748b;
            margin: 0;
        }

        .profile-section {
            background: white;
            border-radius: 18px;
            padding: 28px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .section-title i {
            font-size: 18px;
        }

        .section-title h3 {
            margin: 0;
        }

        .section-description {
            color: #64748b;
            font-size: 14px;
            margin-top: -12px;
            margin-bottom: 20px;
        }


        /* PROFILE PHOTO */

        .photo-upload {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            background: #f1f5f9;
            border: 3px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-placeholder {
            font-size: 42px;
            color: #94a3b8;
        }

        .upload-area {
            flex: 1;
        }

        .upload-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: white;
            font-weight: 500;
            transition: 0.2s;
        }

        .upload-button:hover {
            background: #f8fafc;
        }

        .upload-info {
            display: block;
            margin-top: 8px;
            color: #64748b;
            font-size: 13px;
        }

        #profile_photo {
            display: none;
        }


        /* PROFESSIONS */

        .profession-search {
            position: relative;
        }

        .profession-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .profession-search input {
            width: 100%;
            padding-left: 42px;
        }

        .profession-results {
            margin-top: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            display: none;
            background: white;
            max-height: 220px;
            overflow-y: auto;
        }

        .profession-result {
            padding: 12px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .profession-result:hover {
            background: #f8fafc;
        }

        .profession-result.selected {
            color: #2563eb;
            background: #eff6ff;
        }

        .selected-professions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .profession-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 20px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 14px;
            font-weight: 500;
        }

        .profession-chip button {
            border: none;
            background: none;
            color: #1d4ed8;
            cursor: pointer;
            padding: 0;
            font-size: 14px;
        }

        .quick-professions {
            margin-top: 14px;
        }

        .quick-professions-title {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .quick-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .quick-button {
            border: 1px solid #cbd5e1;
            background: white;
            padding: 7px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
        }

        .quick-button:hover {
            background: #f8fafc;
        }


        /* FORM GRID */

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-grid .full {
            grid-column: 1 / -1;
        }


        /* ACTIONS */

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }

        .cancel-link {
            color: #64748b;
            text-decoration: none;
        }

        .cancel-link:hover {
            color: #1e293b;
        }


        @media (max-width: 650px) {

            .profile-section {
                padding: 20px;
            }

            .photo-upload {
                flex-direction: column;
                text-align: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-grid .full {
                grid-column: auto;
            }

            .form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

        }

    </style>

</head>

<body>

<?php

$base = "../";
$activePage = "dashboard";

include "../includes/navbar.php";

?>

<main id="main-content">

    <div class="edit-profile-page">

        <div class="edit-profile-container">


            <!-- HEADER -->

            <div class="edit-header">

                <h1>Edit Your Profile</h1>

                <p>
                    Keep your information updated so clients know what you can do.
                </p>

            </div>


            <?php if ($message !== ""): ?>

                <div
                    class="alert error"
                    role="alert"
                >
                    <?= e($message) ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- =====================================================
                     PROFILE PHOTO
                ====================================================== -->

                <div class="profile-section">

                    <div class="section-title">

                        <i class="fa-solid fa-camera"></i>

                        <h3>Profile Photo</h3>

                    </div>

                    <p class="section-description">
                        Use a clear photo of yourself. This helps clients recognize you.
                    </p>


                    <div class="photo-upload">

                        <div class="photo-preview">

                            <?php if ($profileImage): ?>

                                <img
                                    src="<?= e($profileImage) ?>"
                                    id="photoPreview"
                                    alt="Profile photo"
                                >

                            <?php else: ?>

                                <img
                                    id="photoPreview"
                                    src=""
                                    alt="Profile photo"
                                    style="display:none;"
                                >

                                <i
                                    class="fa-solid fa-user photo-placeholder"
                                    id="photoPlaceholder"
                                ></i>

                            <?php endif; ?>

                        </div>


                        <div class="upload-area">

                            <label
                                for="profile_photo"
                                class="upload-button"
                            >

                                <i class="fa-solid fa-upload"></i>

                                Choose Photo

                            </label>

                            <input
                                type="file"
                                id="profile_photo"
                                name="profile_photo"
                                accept="image/jpeg,image/png,image/webp"
                            >

                            <span class="upload-info">
                                JPG, PNG or WEBP · Maximum 5MB
                            </span>

                        </div>

                    </div>

                </div>


                <!-- =====================================================
                     PROFESSIONS
                ====================================================== -->

                <div class="profile-section">

                    <div class="section-title">

                        <i class="fa-solid fa-briefcase"></i>

                        <h3>Your Professions</h3>

                    </div>

                    <p class="section-description">
                        Add all the services you offer. You can select more than one.
                    </p>


                    <div class="profession-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="professionSearch"
                            placeholder="Search professions..."
                            autocomplete="off"
                        >

                    </div>


                    <div
                        class="profession-results"
                        id="professionResults"
                    >

                        <?php foreach ($categories as $cat): ?>

                            <div
                                class="profession-result"
                                data-id="<?= $cat['id'] ?>"
                                data-name="<?= e(strtolower($cat['name'])) ?>"
                            >

                                <span>
                                    <?= e($cat['name']) ?>
                                </span>

                                <i class="fa-solid fa-plus"></i>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <div
                        class="selected-professions"
                        id="selectedProfessions"
                    >

                    </div>


                    <div class="quick-professions">

                        <div class="quick-professions-title">
                            Popular professions
                        </div>

                        <div class="quick-buttons">

                            <?php foreach ($categories as $cat): ?>

                                <button
                                    type="button"
                                    class="quick-button"
                                    data-id="<?= $cat['id'] ?>"
                                >
                                    <?= e($cat['name']) ?>
                                </button>

                            <?php endforeach; ?>

                        </div>

                    </div>

                    <div id="categoryInputs"></div>

                </div>


                <!-- =====================================================
                     WORK DETAILS
                ====================================================== -->

                <div class="profile-section">

                    <div class="section-title">

                        <i class="fa-solid fa-user-gear"></i>

                        <h3>Work Details</h3>

                    </div>


                    <div class="form-grid">

                        <div class="field">

                            <label for="experience">
                                Years of Experience
                            </label>

                            <input
                                type="number"
                                id="experience"
                                name="experience"
                                min="0"
                                value="<?= e($worker['experience']) ?>"
                                required
                            >

                        </div>


                        <div class="field">

                            <label for="daily_rate">
                                Daily Rate (Rs.)
                            </label>

                            <input
                                type="number"
                                id="daily_rate"
                                name="daily_rate"
                                min="1"
                                step="0.01"
                                value="<?= e($worker['daily_rate']) ?>"
                                required
                            >

                        </div>


                        <div class="field full">

                            <label for="address">
                                Work Location / Address
                            </label>

                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="<?= e($worker['address']) ?>"
                                placeholder="e.g. Patan, Lalitpur"
                                required
                            >

                        </div>


                        <div class="field full">

                            <label for="bio">
                                About Your Work
                            </label>
<textarea
    id="bio"
    name="bio"
    rows="5"
    style="width: 100%; max-width: 100%; min-height: 140px; box-sizing: border-box; resize: vertical;"
    placeholder="Describe your skills, services and experience..."
    required
><?= e($worker['bio']) ?></textarea>
                        </div>


                        <div class="field full">

                            <label for="availability">
                                Availability
                            </label>

                            <select
                                name="availability"
                                id="availability"
                            >

                                <option
                                    value="Available"
                                    <?= $worker['availability'] === "Available" ? "selected" : "" ?>
                                >
                                   🟢 Available — I can accept work
                                </option>

                                <option
                                    value="Busy"
                                    <?= $worker['availability'] === "Busy" ? "selected" : "" ?>
                                >
                                    🔴 Busy — I'm currently unavailable
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <!-- =====================================================
                     ACTIONS
                ====================================================== -->

                <div class="form-actions">

                    <a
                        href="my_profile.php"
                        class="cancel-link"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        name="update_profile"
                        class="btn primary"
                    >
                        <i class="fa-solid fa-check"></i>
                        Save Changes
                    </button>

                </div>


            </form>

        </div>

    </div>

</main>


<?php

$base = "../";

include "../includes/footer.php";

?>

<script src="../assets/js/main.js" defer></script>


<script>

/*
|--------------------------------------------------------------------------
| PROFILE PHOTO PREVIEW
|--------------------------------------------------------------------------
*/

const photoInput = document.getElementById("profile_photo");
const photoPreview = document.getElementById("photoPreview");
const photoPlaceholder = document.getElementById("photoPlaceholder");

photoInput.addEventListener("change", function () {

    const file = this.files[0];

    if (!file) {
        return;
    }

    if (!file.type.startsWith("image/")) {
        alert("Please choose an image file.");
        this.value = "";
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        alert("The image must be smaller than 5MB.");
        this.value = "";
        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {

        photoPreview.src = event.target.result;
        photoPreview.style.display = "block";

        if (photoPlaceholder) {
            photoPlaceholder.style.display = "none";
        }

    };

    reader.readAsDataURL(file);

});


/*
|--------------------------------------------------------------------------
| PROFESSIONS
|--------------------------------------------------------------------------
*/

const searchInput =
    document.getElementById("professionSearch");

const resultsBox =
    document.getElementById("professionResults");

const selectedBox =
    document.getElementById("selectedProfessions");

const categoryInputs =
    document.getElementById("categoryInputs");

const professionResults =
    document.querySelectorAll(".profession-result");

const quickButtons =
    document.querySelectorAll(".quick-button");


let selectedCategories =
    <?= json_encode($selectedCategoryIds) ?>;


/*
|--------------------------------------------------------------------------
| DRAW SELECTED PROFESSIONS
|--------------------------------------------------------------------------
*/

function renderSelectedProfessions() {

    selectedBox.innerHTML = "";
    categoryInputs.innerHTML = "";

    selectedCategories.forEach(function (id) {

        const result =
            document.querySelector(
                '.profession-result[data-id="' + id + '"]'
            );

        if (!result) {
            return;
        }

        const name =
            result.querySelector("span").textContent.trim();


        /*
        | Visual chip
        */

        const chip =
            document.createElement("div");

        chip.className = "profession-chip";

        chip.innerHTML = `
            <span>${name}</span>
            <button type="button" data-remove="${id}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;

        selectedBox.appendChild(chip);


        /*
        | Hidden form input
        */

        const input =
            document.createElement("input");

        input.type = "hidden";
        input.name = "categories[]";
        input.value = id;

        categoryInputs.appendChild(input);

    });


    /*
    | Update search result appearance
    */

    professionResults.forEach(function (item) {

        const id =
            parseInt(item.dataset.id);

        const icon =
            item.querySelector("i");

        if (selectedCategories.includes(id)) {

            item.classList.add("selected");

            icon.className =
                "fa-solid fa-check";

        } else {

            item.classList.remove("selected");

            icon.className =
                "fa-solid fa-plus";

        }

    });

}


/*
|--------------------------------------------------------------------------
| ADD / REMOVE CATEGORY
|--------------------------------------------------------------------------
*/

function toggleCategory(id) {

    id = parseInt(id);

    const index =
        selectedCategories.indexOf(id);

    if (index === -1) {

        selectedCategories.push(id);

    } else {

        selectedCategories.splice(index, 1);

    }

    renderSelectedProfessions();

}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

searchInput.addEventListener("focus", function () {

    resultsBox.style.display = "block";

    filterProfessions();

});


searchInput.addEventListener("input", function () {

    resultsBox.style.display = "block";

    filterProfessions();

});


function filterProfessions() {

    const search =
        searchInput.value
            .trim()
            .toLowerCase();

    let visible = 0;

    professionResults.forEach(function (item) {

        const name =
            item.dataset.name;

        if (
            search === "" ||
            name.includes(search)
        ) {

            item.style.display = "flex";

            visible++;

        } else {

            item.style.display = "none";

        }

    });

}


/*
|--------------------------------------------------------------------------
| CLICK SEARCH RESULT
|--------------------------------------------------------------------------
*/

professionResults.forEach(function (item) {

    item.addEventListener("click", function () {

        toggleCategory(this.dataset.id);

        searchInput.value = "";

        filterProfessions();

    });

});


/*
|--------------------------------------------------------------------------
| QUICK PROFESSION BUTTONS
|--------------------------------------------------------------------------
*/

quickButtons.forEach(function (button) {

    button.addEventListener("click", function () {

        toggleCategory(this.dataset.id);

    });

});


/*
|--------------------------------------------------------------------------
| REMOVE CHIP
|--------------------------------------------------------------------------
*/

selectedBox.addEventListener("click", function (event) {

    const button =
        event.target.closest("[data-remove]");

    if (!button) {
        return;
    }

    toggleCategory(button.dataset.remove);

});


/*
|--------------------------------------------------------------------------
| CLOSE SEARCH WHEN CLICKING OUTSIDE
|--------------------------------------------------------------------------
*/

document.addEventListener("click", function (event) {

    if (
        !event.target.closest(".profession-search") &&
        !event.target.closest(".profession-results")
    ) {

        resultsBox.style.display = "none";

    }

});


/*
|--------------------------------------------------------------------------
| INITIAL RENDER
|--------------------------------------------------------------------------
*/

renderSelectedProfessions();

</script>

</body>
</html>