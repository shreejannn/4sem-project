<?php
require_once "../config/session.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Get current user
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$user) {
    header("Location: ../login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if (isset($_POST['update'])) {

    $name = trim($_POST['name'] ?? "");
    $phone = trim($_POST['phone'] ?? "");
    $new_password = $_POST['new_password'] ?? "";

    // Keep current avatar by default
    $avatar = $user['avatar'];

    /*
    |--------------------------------------------------------------------------
    | Basic Validation
    |--------------------------------------------------------------------------
    */

    if (empty($name) || empty($phone)) {

        $message = "Please fill in all fields.";

    }


    /*
    |--------------------------------------------------------------------------
    | Remove Profile Picture
    |--------------------------------------------------------------------------
    */

    if (
        $message === "" &&
        isset($_POST['remove_avatar']) &&
        $_POST['remove_avatar'] === "1"
    ) {

        if (
            !empty($user['avatar']) &&
            strpos($user['avatar'], 'uploads/profile/') === 0
        ) {

            $old_file = "../" . $user['avatar'];

            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }

        $avatar = "👤";
    }


    /*
    |--------------------------------------------------------------------------
    | Emoji Avatar
    |--------------------------------------------------------------------------
    |
    | Only use the emoji if no new image is being uploaded.
    |
    */

    if (
        $message === "" &&
        !isset($_POST['remove_avatar']) &&
        empty($_FILES['profile_image']['name']) &&
        isset($_POST['avatar']) &&
        !empty($_POST['avatar'])
    ) {

        $allowed_avatars = [
            "👷",
            "👷‍♀️",
            "👨‍🔧",
            "👩‍🔧",
            "👨‍🏭",
            "👩‍🏭",
            "👨‍🔨",
            "👩‍🔨",
            "🧑‍🔧",
            "👤"
        ];

        if (in_array($_POST['avatar'], $allowed_avatars, true)) {
            $avatar = $_POST['avatar'];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Profile Picture Upload
    |--------------------------------------------------------------------------
    */

    if (
        $message === "" &&
        isset($_FILES['profile_image']) &&
        $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {

            $message = "There was a problem uploading the profile picture.";

        } else {

            $file = $_FILES['profile_image'];

            // Maximum 5 MB
            $max_size = 5 * 1024 * 1024;

            if ($file['size'] > $max_size) {

                $message = "Profile picture must be less than 5 MB.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Check Real MIME Type
                |--------------------------------------------------------------------------
                */

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowed_types = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif'
                ];

                if (!isset($allowed_types[$mime_type])) {

                    $message =
                        "Only JPG, PNG, WEBP and GIF images are allowed.";

                } else {

                    $extension = $allowed_types[$mime_type];

                    $upload_dir = "../uploads/profile/";

                    // Create directory if needed
                    if (!is_dir($upload_dir)) {

                        if (!mkdir($upload_dir, 0755, true)) {

                            $message =
                                "Could not create upload directory.";
                        }
                    }


                    if ($message === "") {

                        /*
                        |--------------------------------------------------------------------------
                        | Generate Safe Unique Filename
                        |--------------------------------------------------------------------------
                        */

                        $filename =
                            "profile_" .
                            bin2hex(random_bytes(16)) .
                            "." .
                            $extension;

                        $target = $upload_dir . $filename;


                        /*
                        |--------------------------------------------------------------------------
                        | Move Uploaded File
                        |--------------------------------------------------------------------------
                        */

                        if (
                            move_uploaded_file(
                                $file['tmp_name'],
                                $target
                            )
                        ) {

                            // Save path relative to project root
                            $avatar =
                                "uploads/profile/" .
                                $filename;


                            /*
                            |--------------------------------------------------------------------------
                            | Delete Old Uploaded Image
                            |--------------------------------------------------------------------------
                            */

                            if (
                                !empty($user['avatar']) &&
                                strpos(
                                    $user['avatar'],
                                    'uploads/profile/'
                                ) === 0
                            ) {

                                $old_file =
                                    "../" .
                                    $user['avatar'];

                                if (file_exists($old_file)) {
                                    unlink($old_file);
                                }
                            }

                        } else {

                            $message =
                                "Failed to save the profile picture.";
                        }
                    }
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Password Update
    |--------------------------------------------------------------------------
    */

    if (
        $message === "" &&
        !empty($new_password)
    ) {

        if (strlen($new_password) < 6) {

            $message =
                "New password must be at least 6 characters.";

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save Changes
    |--------------------------------------------------------------------------
    */

    if ($message === "") {

        if (!empty($new_password)) {

            $hash = password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );

            $update = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET name = ?,
                     phone = ?,
                     avatar = ?,
                     password = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "ssssi",
                $name,
                $phone,
                $avatar,
                $hash,
                $user_id
            );

        } else {

            $update = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET name = ?,
                     phone = ?,
                     avatar = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "sssi",
                $name,
                $phone,
                $avatar,
                $user_id
            );
        }


        if (mysqli_stmt_execute($update)) {

            // Update session
            $_SESSION['name'] = $name;
            $_SESSION['avatar'] = $avatar;

            $_SESSION['success'] =
                "Profile updated successfully.";

            header("Location: dashboard.php");
            exit();

        } else {

            $message =
                "Failed to update profile.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Preserve Form Values After Error
    |--------------------------------------------------------------------------
    */

    $user['name'] = $name;
    $user['phone'] = $phone;
    $user['avatar'] = $avatar;
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

        .profile-image-preview {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .profile-image-preview .avatar-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f1f1;
            border: 3px solid #e5e7eb;
        }

        .profile-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-image-preview .avatar-emoji {
            font-size: 55px;
            line-height: 1;
        }

        .upload-box {
            border: 2px dashed #d1d5db;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
        }

        .upload-box label {
            cursor: pointer;
            display: block;
        }

        .upload-box i {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .upload-box input[type="file"] {
            margin-top: 10px;
            width: 100%;
        }

        .avatar-grid {
            margin-top: 10px;
        }

        .remove-avatar {
            display: block;
            text-align: center;
            margin-top: 10px;
        }

        .remove-avatar label {
            cursor: pointer;
            font-size: 14px;
        }

        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #64748b;
            text-decoration: none;
        }

        .cancel-link:hover {
            color: #1e293b;
        }

    </style>

</head>


<body>


<?php

$base = "../";
$activePage = "dashboard";

include "../includes/navbar.php";

?>


<main id="main-content" class="form-page">

    <div class="form-container">

        <div class="form-card">

            <form
                method="POST"
                enctype="multipart/form-data"
                novalidate
            >

                <h2>Edit Profile</h2>


                <?php if ($message != ""): ?>

                    <div
                        class="alert error"
                        role="alert"
                    >
                        <?= e($message) ?>
                    </div>

                <?php endif; ?>


                <!-- Current Avatar -->

                <div class="profile-image-preview">

                    <div class="avatar-circle">

                        <?php if (
                            !empty($user['avatar']) &&
                            strpos(
                                $user['avatar'],
                                'uploads/profile/'
                            ) === 0
                        ): ?>

                            <img
                                src="../<?= e($user['avatar']) ?>"
                                alt="Current profile picture"
                            >

                        <?php elseif (!empty($user['avatar'])): ?>

                            <span class="avatar-emoji">
                                <?= e($user['avatar']) ?>
                            </span>

                        <?php else: ?>

                            <i class="fa-solid fa-user"></i>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- Name -->

                <div class="field">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($user['name']) ?>"
                        autocomplete="name"
                        required
                    >

                </div>


                <!-- Phone -->

                <div class="field">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?= e($user['phone']) ?>"
                        autocomplete="tel"
                        required
                    >

                </div>


                <!-- Email -->

                <div class="field">

                    <label for="email">

                        Email

                        <span
                            class="hint"
                            style="display:inline;"
                        >
                            (cannot be changed)
                        </span>

                    </label>

                    <input
                        type="email"
                        id="email"
                        value="<?= e($user['email']) ?>"
                        disabled
                    >

                </div>


                <!-- Upload Picture -->

                <div class="field">

                    <label for="profile_image">
                        Profile Picture
                    </label>

                    <div class="upload-box">

                        <label for="profile_image">

                            <i
                                class="fa-solid fa-camera"
                                aria-hidden="true"
                            ></i>

                            <div>
                                Upload a new profile picture
                            </div>

                            <small>
                                JPG, PNG, WEBP or GIF — Max 5 MB
                            </small>

                        </label>

                        <input
                            type="file"
                            id="profile_image"
                            name="profile_image"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                        >

                    </div>


                    <!-- Remove Avatar -->

                    <?php if (!empty($user['avatar'])): ?>

                        <div class="remove-avatar">

                            <label>

                                <input
                                    type="checkbox"
                                    name="remove_avatar"
                                    value="1"
                                >

                                Remove current profile picture

                            </label>

                        </div>

                    <?php endif; ?>

                </div>


          


                <!-- Password -->

                <div class="field">

                    <label for="new_password">

                        New Password

                        <span
                            class="hint"
                            style="display:inline;"
                        >
                            (leave blank to keep current)
                        </span>

                    </label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="New Password"
                        autocomplete="new-password"
                        minlength="6"
                    >

                </div>


                <!-- Update -->

                <button
                    type="submit"
                    name="update"
                    class="btn primary block"
                >
                    Update Profile
                </button>


                <!-- Cancel -->

                <a
                    href="my_profile.php"
                    class="cancel-link"
                >
                    Cancel
                </a>


            </form>

        </div>

    </div>

</main>


<?php

$base = "../";

include "../includes/footer.php";

?>


<script
    src="../assets/js/main.js"
    defer
></script>

</body>

</html>
