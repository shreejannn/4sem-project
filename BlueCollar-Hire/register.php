<?php

require_once "config/session.php";

/*
|--------------------------------------------------------------------------
| BLUECOLLAR-HIRE - REGISTER
|--------------------------------------------------------------------------
|
| Registration supports:
|   - Client accounts
|   - Worker accounts
|   - Multiple worker professions
|   - Automatic default avatar
|   - Worker profile creation
|
| IMPORTANT:
| config/session.php should already contain the e() helper.
| Do NOT define e() again in this file.
|
*/


/* =========================================================
   REDIRECT LOGGED-IN USERS
   ========================================================= */

if (isset($_SESSION['user_id'])) {

    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }

    exit();
}


/* =========================================================
   VARIABLES
   ========================================================= */

$message = "";

$name = "";
$email = "";
$phone = "";
$role = "";

$selectedCategories = [];


/* =========================================================
   PROFESSION DATA
   ========================================================= */

$professionIcons = [

    'Plumber'     => '🔧',
    'Electrician' => '⚡',
    'Carpenter'   => '🪚',
    'Painter'     => '🎨',
    'Cleaner'     => '🧹',
    'Mechanic'    => '🔩',
    'Gardener'    => '🌱',
    'Mason'       => '🧱',
    'Welder'      => '🔥'

];


/*
|--------------------------------------------------------------------------
| DEFAULT AVATAR BY PROFESSION
|--------------------------------------------------------------------------
*/


/* =========================================================
   LOAD CATEGORIES
   ========================================================= */

$categories = [];

$result = mysqli_query(
    $conn,
    "SELECT id, name
     FROM categories
     ORDER BY name ASC"
);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $categories[] = $row;

    }

}


/* =========================================================
   FORM SUBMISSION
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role = $_POST['role'] ?? '';

    $name = trim($_POST['name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $phone = trim($_POST['phone'] ?? '');

    $password = $_POST['password'] ?? '';

    $confirmPassword = $_POST['confirm_password'] ?? '';

    $terms = isset($_POST['terms']);


    /*
    |--------------------------------------------------------------------------
    | CATEGORY IDs
    |--------------------------------------------------------------------------
    */

    $selectedCategories = $_POST['categories'] ?? [];

    if (!is_array($selectedCategories)) {
        $selectedCategories = [];
    }

    $selectedCategories = array_map(
        'intval',
        $selectedCategories
    );

    $selectedCategories = array_unique(
        $selectedCategories
    );

    $selectedCategories = array_values(
        array_filter(
            $selectedCategories,
            function ($id) {
                return $id > 0;
            }
        )
    );


    /* =====================================================
       BASIC VALIDATION
       ===================================================== */

    if (
        empty($role) ||
        empty($name) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirmPassword)
    ) {

        $message = "Please complete all required fields.";

    }


    elseif (!in_array($role, ['client', 'worker'], true)) {

        $message = "Please choose a valid account type.";

    }


    elseif (strlen($name) < 2) {

        $message = "Please enter your full name.";

    }


    elseif (strlen($name) > 100) {

        $message = "Your name is too long.";

    }


    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    }


    elseif (strlen($email) > 150) {

        $message = "Your email address is too long.";

    }


    elseif (!preg_match('/^[0-9]{10}$/', $phone)) {

        $message = "Phone number must contain exactly 10 digits.";

    }


    elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";

    }


    elseif (
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {

        $message =
            "Password must contain uppercase, lowercase and a number.";

    }


    elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";

    }


    elseif (!$terms) {

        $message =
            "Please agree to the Terms of Service and Privacy Policy.";

    }


    /*
    |--------------------------------------------------------------------------
    | WORKER MUST HAVE AT LEAST ONE PROFESSION
    |--------------------------------------------------------------------------
    */

    elseif (
        $role === 'worker' &&
        empty($selectedCategories)
    ) {

        $message =
            "Please select at least one profession.";

    }


    /* =====================================================
       CHECK EMAIL
       ===================================================== */

    if ($message === "") {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $message =
                "Unable to check your account. Please try again.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {

                $message =
                    "An account with this email already exists.";

            }

            mysqli_stmt_close($stmt);

        }

    }


    /* =====================================================
       VALIDATE CATEGORY IDS
       ===================================================== */

    if (
        $message === "" &&
        $role === 'worker'
    ) {

        $validCategories = [];

        $categoryStmt = mysqli_prepare(
            $conn,
            "SELECT id
             FROM categories
             WHERE id = ?
             LIMIT 1"
        );

        if (!$categoryStmt) {

            $message =
                "Unable to validate professions.";

        } else {

            foreach ($selectedCategories as $categoryId) {

                mysqli_stmt_bind_param(
                    $categoryStmt,
                    "i",
                    $categoryId
                );

                mysqli_stmt_execute(
                    $categoryStmt
                );

                mysqli_stmt_store_result(
                    $categoryStmt
                );

                if (
                    mysqli_stmt_num_rows(
                        $categoryStmt
                    ) > 0
                ) {

                    $validCategories[] =
                        $categoryId;

                }

                mysqli_stmt_free_result(
                    $categoryStmt
                );
            }

            mysqli_stmt_close(
                $categoryStmt
            );

            $selectedCategories =
                array_values(
                    array_unique(
                        $validCategories
                    )
                );


            if (empty($selectedCategories)) {

                $message =
                    "Please select at least one valid profession.";

            }

        }

    }


    /* =====================================================
       CREATE ACCOUNT
       ===================================================== */

    if ($message === "") {

        mysqli_begin_transaction($conn);

        try {

            /* -------------------------------------------------
               PASSWORD
               ------------------------------------------------- */

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


/* -------------------------------------------------
   DEFAULT AVATAR
   ------------------------------------------------- */

// Client = 👤
// Worker = 👷

if ($role === 'worker') {
    $avatar = "👷";
} else {
    $avatar = "👤";
}

            /* =================================================
               INSERT USER
               ================================================= */

            $userStmt = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (
                    name,
                    email,
                    phone,
                    password,
                    role,
                    avatar
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );


            if (!$userStmt) {

                throw new Exception(
                    "Unable to create account."
                );

            }


            mysqli_stmt_bind_param(
                $userStmt,
                "ssssss",
                $name,
                $email,
                $phone,
                $passwordHash,
                $role,
                $avatar
            );


            if (
                !mysqli_stmt_execute(
                    $userStmt
                )
            ) {

                throw new Exception(
                    "Unable to create account."
                );

            }


            $userId =
                mysqli_insert_id($conn);


            mysqli_stmt_close(
                $userStmt
            );


            /* =================================================
               WORKER
               ================================================= */

            if ($role === 'worker') {

                /*
                |--------------------------------------------------------------------------
                | CREATE EMPTY WORKER PROFILE
                |--------------------------------------------------------------------------
                |
                | Details such as:
                | address
                | experience
                | daily rate
                | bio
                | profile picture
                |
                | can be completed later.
                |
                */

                $profileStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO worker_profiles
                    (
                        user_id,
                        experience,
                        daily_rate,
                        address,
                        bio,
                        availability,
                        status
                    )
                    VALUES
                    (
                        ?,
                        0,
                        0,
                        NULL,
                        NULL,
                        'Available',
                        'Pending'
                    )"
                );


                if (!$profileStmt) {

                    throw new Exception(
                        "Unable to create worker profile."
                    );

                }


                mysqli_stmt_bind_param(
                    $profileStmt,
                    "i",
                    $userId
                );


                if (
                    !mysqli_stmt_execute(
                        $profileStmt
                    )
                ) {

                    throw new Exception(
                        "Unable to create worker profile."
                    );

                }


                $workerProfileId =
                    mysqli_insert_id($conn);


                mysqli_stmt_close(
                    $profileStmt
                );


                /* =============================================
                   SAVE MULTIPLE PROFESSIONS
                   ============================================= */

                $professionStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO worker_categories
                    (
                        worker_profile_id,
                        category_id
                    )
                    VALUES (?, ?)"
                );


                if (!$professionStmt) {

                    throw new Exception(
                        "Unable to save worker professions."
                    );

                }


                foreach (
                    $selectedCategories
                    as $categoryId
                ) {

                    mysqli_stmt_bind_param(
                        $professionStmt,
                        "ii",
                        $workerProfileId,
                        $categoryId
                    );


                    if (
                        !mysqli_stmt_execute(
                            $professionStmt
                        )
                    ) {

                        throw new Exception(
                            "Unable to save worker professions."
                        );

                    }

                }


                mysqli_stmt_close(
                    $professionStmt
                );

            }


            /* =================================================
               SUCCESS
               ================================================= */

            mysqli_commit($conn);


            header(
                "Location: login.php?registered=1"
            );

            exit();


        } catch (Throwable $e) {

            mysqli_rollback($conn);

            $message =
                "Registration could not be completed. Please try again.";

        }

    }

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

    <title>
        Create Account | BlueCollar-Hire
    </title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
    >


    <link rel="stylesheet" href="assets/css/style.css">



    <style>

        /* =====================================================
           REGISTER PAGE
           ===================================================== */

        :root {

            --register-blue: #2563eb;
            --register-blue-dark: #1d4ed8;
            --register-blue-light: #eff6ff;

            --register-text: #0f172a;
            --register-muted: #64748b;

            --register-border: #e2e8f0;

            --register-bg: #f8fafc;

        }


        * {
            box-sizing: border-box;
        }


        body {
            font-family: "Inter", sans-serif;
        }


        .register-wrapper {

            min-height:
                calc(100vh - 70px);

            padding:
                50px 20px 70px;

            background:
                linear-gradient(
                    180deg,
                    #f8fafc 0%,
                    #ffffff 100%
                );

        }


        .register-shell {

            width: 100%;

            max-width: 850px;

            margin: auto;

        }


        /* =====================================================
           HEADER
           ===================================================== */

        .register-header {

            text-align: center;

            margin-bottom: 30px;

        }


        .register-logo {

            width: 58px;

            height: 58px;

            margin:
                0 auto 16px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 16px;

            background:
                var(--register-blue);

            color: white;

            font-size: 24px;

            box-shadow:
                0 10px 25px
                rgba(37, 99, 235, .18);

        }


        .register-header h1 {

            margin: 0 0 8px;

            font-size:
                clamp(1.7rem, 4vw, 2.25rem);

            font-weight: 800;

            letter-spacing: -0.03em;

            color:
                var(--register-text);

        }


        .register-header p {

            margin: 0;

            color:
                var(--register-muted);

            font-size: .95rem;

        }


        /* =====================================================
           MAIN CARD
           ===================================================== */

        .register-card {

            background: white;

            border:
                1px solid
                var(--register-border);

            border-radius: 20px;

            padding:
                clamp(22px, 5vw, 42px);

            box-shadow:
                0 20px 60px
                rgba(15, 23, 42, .08);

        }


        /* =====================================================
           ERROR
           ===================================================== */

        .register-alert {

            display: flex;

            align-items: flex-start;

            gap: 11px;

            padding: 14px 16px;

            margin-bottom: 25px;

            border-radius: 10px;

            border:
                1px solid #fecaca;

            background:
                #fef2f2;

            color:
                #b91c1c;

            font-size: .88rem;

        }


        .register-alert i {
            margin-top: 2px;
        }


        /* =====================================================
           PROGRESS
           ===================================================== */

        .register-progress {

            display: flex;

            align-items: center;

            margin-bottom: 35px;

        }


        .progress-step {

            display: flex;

            align-items: center;

            gap: 9px;

            color:
                #94a3b8;

            font-size: .82rem;

            font-weight: 600;

        }


        .progress-number {

            width: 30px;

            height: 30px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background:
                #f1f5f9;

            color:
                #64748b;

        }


        .progress-step.active {

            color:
                var(--register-blue);

        }


        .progress-step.active
        .progress-number {

            background:
                var(--register-blue);

            color: white;

        }


        .progress-line {

            flex: 1;

            height: 1px;

            margin:
                0 15px;

            background:
                var(--register-border);

        }


        /* =====================================================
           ROLE SELECTION
           ===================================================== */

        .section-title {

            margin: 0 0 7px;

            font-size: 1.1rem;

            font-weight: 700;

            color:
                var(--register-text);

        }


        .section-description {

            margin: 0 0 22px;

            color:
                var(--register-muted);

            font-size: .86rem;

        }


        .role-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 15px;

            margin-bottom: 30px;

        }


        .role-choice {

            position: relative;

        }


        .role-choice input {

            position: absolute;

            opacity: 0;

            pointer-events: none;

        }


        .role-choice label {

            min-height: 170px;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            text-align: center;

            padding: 25px 18px;

            border:
                2px solid
                var(--register-border);

            border-radius: 16px;

            cursor: pointer;

            transition:
                .2s ease;

        }


        .role-choice label:hover {

            border-color:
                #94a3b8;

            transform:
                translateY(-2px);

        }


        .role-choice input:checked
        + label {

            border-color:
                var(--register-blue);

            background:
                var(--register-blue-light);

            box-shadow:
                0 8px 25px
                rgba(37, 99, 235, .10);

        }


        .role-icon {

            width: 58px;

            height: 58px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 13px;

            border-radius: 16px;

            background:
                #f1f5f9;

            color:
                #475569;

            font-size: 25px;

        }


        .role-choice input:checked
        + label
        .role-icon {

            background:
                #dbeafe;

            color:
                var(--register-blue);

        }


        .role-name {

            font-size: 1.05rem;

            font-weight: 700;

            color:
                var(--register-text);

        }


        .role-description {

            margin-top: 5px;

            color:
                var(--register-muted);

            font-size: .82rem;

        }


        /* =====================================================
           FORM
           ===================================================== */

        .account-form {

            display: none;

        }


        .account-form.active {

            display: block;

            animation:
                slideUp .25s ease;

        }


        @keyframes slideUp {

            from {

                opacity: 0;

                transform:
                    translateY(8px);

            }

            to {

                opacity: 1;

                transform:
                    translateY(0);

            }

        }


        .form-divider {

            height: 1px;

            background:
                var(--register-border);

            margin:
                5px 0 28px;

        }


        .form-section {

            margin-bottom: 28px;

        }


        .form-section-heading {

            margin-bottom: 18px;

        }


        .form-section-heading h2 {

            margin: 0 0 5px;

            font-size: 1.15rem;

            font-weight: 700;

            color:
                var(--register-text);

        }


        .form-section-heading p {

            margin: 0;

            color:
                var(--register-muted);

            font-size: .83rem;

        }


        .field-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 17px;

        }


        .field {

            margin-bottom: 17px;

        }


        .field label {

            display: block;

            margin-bottom: 7px;

            color:
                #334155;

            font-size: .82rem;

            font-weight: 600;

        }


        .input-wrapper {

            position: relative;

        }


        .input-icon {

            position: absolute;

            left: 14px;

            top: 50%;

            transform:
                translateY(-50%);

            color:
                #94a3b8;

            pointer-events: none;

        }


        .field input {

            width: 100%;

            height: 48px;

            padding:
                0 14px 0 42px;

            border:
                1px solid #cbd5e1;

            border-radius: 9px;

            background: white;

            color:
                var(--register-text);

            font-family: inherit;

            font-size: .88rem;

            outline: none;

            transition:
                .2s ease;

        }


        .field input:focus {

            border-color:
                var(--register-blue);

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, .10);

        }


        .password-toggle {

            position: absolute;

            right: 12px;

            top: 50%;

            transform:
                translateY(-50%);

            border: 0;

            background: transparent;

            color:
                #94a3b8;

            cursor: pointer;

            padding: 5px;

        }


        .password-toggle:hover {

            color:
                #475569;

        }


        .field input.has-toggle {

            padding-right: 45px;

        }


        /* =====================================================
           PASSWORD STRENGTH
           ===================================================== */

        .password-strength {

            margin-top: 8px;

        }


        .strength-bar {

            height: 4px;

            overflow: hidden;

            border-radius: 10px;

            background:
                #e2e8f0;

        }


        .strength-fill {

            width: 0;

            height: 100%;

            transition:
                .25s ease;

        }


        .strength-text {

            display: block;

            margin-top: 5px;

            color:
                #94a3b8;

            font-size: .72rem;

        }


        /* =====================================================
           PROFESSIONS
           ===================================================== */

        .profession-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 11px;

        }


        .profession {

            position: relative;

        }


        .profession input {

            position: absolute;

            opacity: 0;

            pointer-events: none;

        }


        .profession label {

            min-height: 92px;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            gap: 6px;

            border:
                1px solid #cbd5e1;

            border-radius: 11px;

            cursor: pointer;

            text-align: center;

            transition:
                .2s ease;

        }


        .profession label:hover {

            border-color:
                #94a3b8;

            background:
                #f8fafc;

        }


        .profession input:checked
        + label {

            border-color:
                var(--register-blue);

            background:
                var(--register-blue-light);

            color:
                var(--register-blue);

        }


        .profession input:focus-visible
        + label {

            outline:
                3px solid
                rgba(37, 99, 235, .18);

        }


        .profession-icon {

            font-size: 1.5rem;

        }


        .profession-name {

            font-size: .76rem;

            font-weight: 600;

        }


        .selected-count {

            margin-top: 10px;

            color:
                var(--register-muted);

            font-size: .76rem;

        }


        /* =====================================================
           TERMS
           ===================================================== */

        .terms {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            margin:
                3px 0 20px;

        }


        .terms input {

            width: 17px;

            height: 17px;

            margin-top: 2px;

            accent-color:
                var(--register-blue);

        }


        .terms label {

            color:
                var(--register-muted);

            font-size: .78rem;

            line-height: 1.5;

        }


        .terms a {

            color:
                var(--register-blue);

            font-weight: 600;

            text-decoration: none;

        }


        .terms a:hover {

            text-decoration: underline;

        }


        /* =====================================================
           SUBMIT
           ===================================================== */

        .submit-button {

            width: 100%;

            height: 51px;

            border: 0;

            border-radius: 9px;

            background:
                var(--register-blue);

            color: white;

            font-family: inherit;

            font-size: .9rem;

            font-weight: 700;

            cursor: pointer;

            transition:
                .2s ease;

        }


        .submit-button:hover {

            background:
                var(--register-blue-dark);

            transform:
                translateY(-1px);

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, .20);

        }


        .submit-button i {

            margin-right: 7px;

        }


        .back-button {

            width: 100%;

            margin-top: 10px;

            padding: 10px;

            border: 0;

            background: transparent;

            color:
                var(--register-blue);

            font-family: inherit;

            font-size: .8rem;

            font-weight: 600;

            cursor: pointer;

        }


        /* =====================================================
           LOGIN
           ===================================================== */

        .login-link {

            margin-top: 22px;

            text-align: center;

            color:
                var(--register-muted);

            font-size: .84rem;

        }


        .login-link a {

            color:
                var(--register-blue);

            font-weight: 700;

            text-decoration: none;

        }


        .login-link a:hover {

            text-decoration: underline;

        }


        /* =====================================================
           MOBILE
           ===================================================== */

        @media (max-width: 650px) {

            .register-wrapper {

                padding:
                    30px 14px 50px;

            }


            .register-card {

                padding: 20px 16px;

                border-radius: 15px;

            }


            .role-grid {

                grid-template-columns: 1fr;

            }


            .role-choice label {

                min-height: 135px;

            }


            .field-grid {

                grid-template-columns: 1fr;

                gap: 0;

            }


            .profession-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 390px) {

            .profession-grid {

                grid-template-columns: 1fr 1fr;

            }


            .profession label {

                min-height: 80px;

            }

        }


    </style>

</head>


<body>


<?php

$base = "";

include "includes/navbar.php";

?>


<main class="register-wrapper">

    <div class="register-shell">


        <!-- =================================================
             HEADER
             ================================================= -->

        <div class="register-header">

            <div class="register-logo">

                <i
                    class="fa-solid fa-handshake"
                    aria-hidden="true"
                ></i>

            </div>


            <h1>
                Join BlueCollar-Hire
            </h1>


            <p>
                Find skilled professionals or grow your service business.
            </p>

        </div>


        <!-- =================================================
             CARD
             ================================================= -->

        <div class="register-card">


            <!-- =================================================
                 ERROR
                 ================================================= -->

            <?php if ($message !== ""): ?>

                <div
                    class="register-alert"
                    role="alert"
                >

                    <i
                        class="fa-solid fa-circle-exclamation"
                    ></i>

                    <span>
                        <?= e($message) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 PROGRESS
                 ================================================= -->

            <div class="register-progress">

                <div
                    class="progress-step active"
                    id="progress-one"
                >

                    <span class="progress-number">
                        1
                    </span>

                    <span>
                        Account type
                    </span>

                </div>


                <div class="progress-line"></div>


                <div
                    class="progress-step"
                    id="progress-two"
                >

                    <span class="progress-number">
                        2
                    </span>

                    <span>
                        Your details
                    </span>

                </div>

            </div>


            <!-- =================================================
                 ACCOUNT TYPE
                 ================================================= -->

            <section>

                <h2 class="section-title">
                    How will you use BlueCollar-Hire?
                </h2>

                <p class="section-description">
                    Choose the account that matches what you want to do.
                </p>


                <div class="role-grid">


                    <!-- CLIENT -->

                    <div class="role-choice">

                        <input
                            type="radio"
                            id="role-client"
                            name="role_selector"
                            value="client"
                            <?= $role === 'client'
                                ? 'checked'
                                : '' ?>
                        >

                        <label for="role-client">

                            <span class="role-icon">

                                <i
                                    class="fa-solid fa-user"
                                ></i>

                            </span>


                            <span class="role-name">
                                I'm a Client
                            </span>


                            <span class="role-description">
                                I need to hire skilled workers
                            </span>

                        </label>

                    </div>


                    <!-- WORKER -->

                    <div class="role-choice">

                        <input
                            type="radio"
                            id="role-worker"
                            name="role_selector"
                            value="worker"
                            <?= $role === 'worker'
                                ? 'checked'
                                : '' ?>
                        >

                        <label for="role-worker">

                            <span class="role-icon">

                                <i
                                    class="fa-solid fa-screwdriver-wrench"
                                ></i>

                            </span>


                            <span class="role-name">
                                I'm a Skilled Worker
                            </span>


                            <span class="role-description">
                                I want to offer my services
                            </span>

                        </label>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 CLIENT FORM
                 ================================================= -->

            <form
                method="POST"
                action="register.php"
                class="account-form
                    <?= $role === 'client'
                        ? 'active'
                        : '' ?>"
                id="client-form"
            >

                <input
                    type="hidden"
                    name="role"
                    value="client"
                >


                <div class="form-divider"></div>


                <div class="form-section">

                    <div class="form-section-heading">

                        <h2>
                            Your details
                        </h2>

                        <p>
                            Tell us a little about yourself.
                        </p>

                    </div>


                    <div class="field-grid">


                        <!-- NAME -->

                        <div class="field">

                            <label for="client-name">
                                Full name
                            </label>

                            <div class="input-wrapper">

                                <i
                                    class="fa-regular fa-user input-icon"
                                ></i>

                                <input
                                    type="text"
                                    id="client-name"
                                    name="name"
                                    placeholder="Your full name"
                                    value="<?= $role === 'client'
                                        ? e($name)
                                        : '' ?>"
                                    maxlength="100"
                                    autocomplete="name"
                                    required
                                >

                            </div>

                        </div>


                        <!-- PHONE -->

                        <div class="field">

                            <label for="client-phone">
                                Phone number
                            </label>

                            <div class="input-wrapper">

                                <i
                                    class="fa-solid fa-phone input-icon"
                                ></i>

                                <input
                                    type="tel"
                                    id="client-phone"
                                    name="phone"
                                    placeholder="98XXXXXXXX"
                                    value="<?= $role === 'client'
                                        ? e($phone)
                                        : '' ?>"
                                    maxlength="10"
                                    inputmode="numeric"
                                    autocomplete="tel"
                                    required
                                >

                            </div>

                        </div>

                    </div>


                    <!-- EMAIL -->

                    <div class="field">

                        <label for="client-email">
                            Email address
                        </label>

                        <div class="input-wrapper">

                            <i
                                class="fa-regular fa-envelope input-icon"
                            ></i>

                            <input
                                type="email"
                                id="client-email"
                                name="email"
                                placeholder="you@example.com"
                                value="<?= $role === 'client'
                                    ? e($email)
                                    : '' ?>"
                                maxlength="150"
                                autocomplete="email"
                                required
                            >

                        </div>

                    </div>


                    <!-- PASSWORD -->

                    <div class="field">

                        <label for="client-password">
                            Password
                        </label>

                        <div class="input-wrapper">

                            <i
                                class="fa-solid fa-lock input-icon"
                            ></i>

                            <input
                                type="password"
                                id="client-password"
                                name="password"
                                class="has-toggle"
                                placeholder="Create a strong password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="client-password"
                                aria-label="Show password"
                            >

                                <i
                                    class="fa-regular fa-eye"
                                ></i>

                            </button>

                        </div>


                        <div class="password-strength">

                            <div class="strength-bar">

                                <div
                                    class="strength-fill"
                                    id="client-strength"
                                ></div>

                            </div>

                            <span
                                class="strength-text"
                                id="client-strength-text"
                            >
                                Use 8+ characters with uppercase, lowercase and a number.
                            </span>

                        </div>

                    </div>


                    <!-- CONFIRM -->

                    <div class="field">

                        <label for="client-confirm">
                            Confirm password
                        </label>

                        <div class="input-wrapper">

                            <i
                                class="fa-solid fa-lock input-icon"
                            ></i>

                            <input
                                type="password"
                                id="client-confirm"
                                name="confirm_password"
                                class="has-toggle"
                                placeholder="Enter your password again"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="client-confirm"
                                aria-label="Show password"
                            >

                                <i
                                    class="fa-regular fa-eye"
                                ></i>

                            </button>

                        </div>

                    </div>


                    <!-- TERMS -->

                    <div class="terms">

                        <input
                            type="checkbox"
                            id="client-terms"
                            name="terms"
                            required
                        >

                        <label for="client-terms">

                            I agree to the
                            <a href="#">
                                Terms of Service
                            </a>
                            and
                            <a href="#">
                                Privacy Policy
                            </a>.

                        </label>

                    </div>


                    <button
                        type="submit"
                        class="submit-button"
                    >

                        <i
                            class="fa-solid fa-user-plus"
                        ></i>

                        Create Client Account

                    </button>


                    <button
                        type="button"
                        class="back-button"
                        data-back
                    >

                        ← Change account type

                    </button>

                </div>

            </form>


            <!-- =================================================
                 WORKER FORM
                 ================================================= -->

            <form
                method="POST"
                action="register.php"
                class="account-form
                    <?= $role === 'worker'
                        ? 'active'
                        : '' ?>"
                id="worker-form"
            >

                <input
                    type="hidden"
                    name="role"
                    value="worker"
                >


                <div class="form-divider"></div>


                <div class="form-section">


                    <div class="form-section-heading">

                        <h2>
                            Create your worker account
                        </h2>

                        <p>
                            Your public profile can be completed after registration.
                        </p>

                    </div>


                    <!-- NAME + PHONE -->

                    <div class="field-grid">


                        <div class="field">

                            <label for="worker-name">
                                Full name
                            </label>

                            <div class="input-wrapper">

                                <i
                                    class="fa-regular fa-user input-icon"
                                ></i>

                                <input
                                    type="text"
                                    id="worker-name"
                                    name="name"
                                    placeholder="Your full name"
                                    value="<?= $role === 'worker'
                                        ? e($name)
                                        : '' ?>"
                                    maxlength="100"
                                    autocomplete="name"
                                    required
                                >

                            </div>

                        </div>


                        <div class="field">

                            <label for="worker-phone">
                                Phone number
                            </label>

                            <div class="input-wrapper">

                                <i
                                    class="fa-solid fa-phone input-icon"
                                ></i>

                                <input
                                    type="tel"
                                    id="worker-phone"
                                    name="phone"
                                    placeholder="98XXXXXXXX"
                                    value="<?= $role === 'worker'
                                        ? e($phone)
                                        : '' ?>"
                                    maxlength="10"
                                    inputmode="numeric"
                                    autocomplete="tel"
                                    required
                                >

                            </div>

                        </div>


                    </div>


                    <!-- EMAIL -->

                    <div class="field">

                        <label for="worker-email">
                            Email address
                        </label>

                        <div class="input-wrapper">

                            <i
                                class="fa-regular fa-envelope input-icon"
                            ></i>

                            <input
                                type="email"
                                id="worker-email"
                                name="email"
                                placeholder="you@example.com"
                                value="<?= $role === 'worker'
                                    ? e($email)
                                    : '' ?>"
                                maxlength="150"
                                autocomplete="email"
                                required
                            >

                        </div>

                    </div>


                    <!-- =================================================
                         PROFESSIONS
                         ================================================= -->

                    <div class="form-section-heading">

                        <h2>
                            What services do you provide?
                        </h2>

                        <p>
                            Select every profession you work in.
                        </p>

                    </div>


                    <div class="profession-grid">


                        <?php foreach (
                            $categories
                            as $category
                        ): ?>


                            <?php

                            $categoryId =
                                (int) $category['id'];

                            $categoryName =
                                $category['name'];

                            $icon =
                                $professionIcons[
                                    $categoryName
                                ] ?? '🛠️';

                            $checked =
                                in_array(
                                    $categoryId,
                                    $selectedCategories,
                                    true
                                );

                            ?>


                            <div class="profession">

                                <input
                                    type="checkbox"
                                    id="category-<?= $categoryId ?>"
                                    name="categories[]"
                                    value="<?= $categoryId ?>"
                                    <?= $checked
                                        ? 'checked'
                                        : '' ?>
                                >


                                <label
                                    for="category-<?= $categoryId ?>"
                                >

                                    <span
                                        class="profession-icon"
                                    >
                                        <?= e($icon) ?>
                                    </span>

                                    <span
                                        class="profession-name"
                                    >
                                        <?= e($categoryName) ?>
                                    </span>

                                </label>

                            </div>


                        <?php endforeach; ?>


                    </div>


                    <div
                        class="selected-count"
                        id="selected-count"
                    >
                        No professions selected
                    </div>


                    <!-- PASSWORD -->

                    <div
                        class="field"
                        style="margin-top: 24px;"
                    >

                        <label for="worker-password">
                            Password
                        </label>

                        <div class="input-wrapper">

                            <i
                                class="fa-solid fa-lock input-icon"
                            ></i>

                            <input
                                type="password"
                                id="worker-password"
                                name="password"
                                class="has-toggle"
                                placeholder="Create a strong password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="worker-password"
                                aria-label="Show password"
                            >

                                <i
                                    class="fa-regular fa-eye"
                                ></i>

                            </button>

                        </div>


                        <div class="password-strength">

                            <div class="strength-bar">

                                <div
                                    class="strength-fill"
                                    id="worker-strength"
                                ></div>

                            </div>

                            <span
                                class="strength-text"
                                id="worker-strength-text"
                            >
                                Use 8+ characters with uppercase, lowercase and a number.
                            </span>

                        </div>

                    </div>


                    <!-- CONFIRM -->

                    <div class="field">

                        <label for="worker-confirm">
                            Confirm password
                        </label>

                        <div class="input-wrapper">

                            <i
                                class="fa-solid fa-lock input-icon"
                            ></i>

                            <input
                                type="password"
                                id="worker-confirm"
                                name="confirm_password"
                                class="has-toggle"
                                placeholder="Enter your password again"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="worker-confirm"
                                aria-label="Show password"
                            >

                                <i
                                    class="fa-regular fa-eye"
                                ></i>

                            </button>

                        </div>

                    </div>


                    <!-- TERMS -->

                    <div class="terms">

                        <input
                            type="checkbox"
                            id="worker-terms"
                            name="terms"
                            required
                        >

                        <label for="worker-terms">

                            I agree to the
                            <a href="#">
                                Terms of Service
                            </a>
                            and
                            <a href="#">
                                Privacy Policy
                            </a>.

                        </label>

                    </div>


                    <button
                        type="submit"
                        class="submit-button"
                    >

                        <i
                            class="fa-solid fa-user-plus"
                        ></i>

                        Create Worker Account

                    </button>


                    <button
                        type="button"
                        class="back-button"
                        data-back
                    >

                        ← Change account type

                    </button>

                </div>

            </form>


        </div>


        <!-- =================================================
             LOGIN
             ================================================= -->

        <div class="login-link">

            Already have an account?

            <a href="login.php">
                Sign in
            </a>

        </div>


    </div>

</main>


<?php

$base = "";

include "includes/footer.php";

?>


<script
    src="assets/js/main.js"
    defer
></script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =================================================
           ELEMENTS
           ================================================= */

        const clientChoice =
            document.getElementById(
                "role-client"
            );

        const workerChoice =
            document.getElementById(
                "role-worker"
            );

        const clientForm =
            document.getElementById(
                "client-form"
            );

        const workerForm =
            document.getElementById(
                "worker-form"
            );

        const progressOne =
            document.getElementById(
                "progress-one"
            );

        const progressTwo =
            document.getElementById(
                "progress-two"
            );


        /* =================================================
           SHOW FORM
           ================================================= */

        function showForm(type) {

            if (type === "client") {

                clientForm.classList.add(
                    "active"
                );

                workerForm.classList.remove(
                    "active"
                );

                progressTwo.classList.add(
                    "active"
                );

                setTimeout(
                    function () {

                        document
                            .getElementById(
                                "client-name"
                            )
                            .focus();

                    },
                    150
                );

            }


            if (type === "worker") {

                workerForm.classList.add(
                    "active"
                );

                clientForm.classList.remove(
                    "active"
                );

                progressTwo.classList.add(
                    "active"
                );

                setTimeout(
                    function () {

                        document
                            .getElementById(
                                "worker-name"
                            )
                            .focus();

                    },
                    150
                );

            }

        }


        /* =================================================
           ROLE SELECTION
           ================================================= */

        clientChoice.addEventListener(
            "change",
            function () {

                if (this.checked) {

                    showForm("client");

                }

            }
        );


        workerChoice.addEventListener(
            "change",
            function () {

                if (this.checked) {

                    showForm("worker");

                }

            }
        );


        /* =================================================
           BACK BUTTONS
           ================================================= */

        document
            .querySelectorAll("[data-back]")
            .forEach(
                function (button) {

                    button.addEventListener(
                        "click",
                        function () {

                            clientChoice.checked =
                                false;

                            workerChoice.checked =
                                false;

                            clientForm.classList.remove(
                                "active"
                            );

                            workerForm.classList.remove(
                                "active"
                            );

                            progressTwo.classList.remove(
                                "active"
                            );

                            document
                                .querySelector(
                                    ".register-card"
                                )
                                .scrollIntoView({
                                    behavior: "smooth",
                                    block: "start"
                                });

                        }
                    );

                }
            );


        /* =================================================
           PASSWORD SHOW / HIDE
           ================================================= */

        document
            .querySelectorAll(".password-toggle")
            .forEach(
                function (button) {

                    button.addEventListener(
                        "click",
                        function () {

                            const targetId =
                                this.dataset.target;

                            const input =
                                document.getElementById(
                                    targetId
                                );

                            const icon =
                                this.querySelector(
                                    "i"
                                );


                            if (
                                input.type ===
                                "password"
                            ) {

                                input.type =
                                    "text";

                                icon.className =
                                    "fa-regular fa-eye-slash";

                                this.setAttribute(
                                    "aria-label",
                                    "Hide password"
                                );

                            } else {

                                input.type =
                                    "password";

                                icon.className =
                                    "fa-regular fa-eye";

                                this.setAttribute(
                                    "aria-label",
                                    "Show password"
                                );

                            }

                        }
                    );

                }
            );


        /* =================================================
           PASSWORD STRENGTH
           ================================================= */

        function setupPasswordStrength(
            inputId,
            barId,
            textId
        ) {

            const input =
                document.getElementById(
                    inputId
                );

            const bar =
                document.getElementById(
                    barId
                );

            const text =
                document.getElementById(
                    textId
                );


            if (!input) {
                return;
            }


            input.addEventListener(
                "input",
                function () {

                    const value =
                        this.value;

                    let score = 0;


                    if (
                        value.length >= 8
                    ) {
                        score++;
                    }


                    if (
                        /[A-Z]/.test(value)
                    ) {
                        score++;
                    }


                    if (
                        /[a-z]/.test(value)
                    ) {
                        score++;
                    }


                    if (
                        /[0-9]/.test(value)
                    ) {
                        score++;
                    }


                    if (
                        /[^A-Za-z0-9]/.test(value)
                    ) {
                        score++;
                    }


                    const widths =
                        [
                            "0%",
                            "20%",
                            "40%",
                            "60%",
                            "80%",
                            "100%"
                        ];


                    bar.style.width =
                        widths[score];


                    if (score <= 1) {

                        text.textContent =
                            "Weak password";

                    }

                    else if (score === 2) {

                        text.textContent =
                            "Fair password";

                    }

                    else if (score === 3) {

                        text.textContent =
                            "Good password";

                    }

                    else if (score === 4) {

                        text.textContent =
                            "Strong password";

                    }

                    else {

                        text.textContent =
                            "Very strong password";

                    }

                }
            );

        }


        setupPasswordStrength(
            "client-password",
            "client-strength",
            "client-strength-text"
        );


        setupPasswordStrength(
            "worker-password",
            "worker-strength",
            "worker-strength-text"
        );


        /* =================================================
           PROFESSION COUNTER
           ================================================= */

        const professionCheckboxes =
            document.querySelectorAll(
                "#worker-form input[name='categories[]']"
            );

        const selectedCount =
            document.getElementById(
                "selected-count"
            );


        function updateProfessionCount() {

            const count =
                Array.from(
                    professionCheckboxes
                ).filter(
                    function (checkbox) {

                        return checkbox.checked;

                    }
                ).length;


            if (count === 0) {

                selectedCount.textContent =
                    "No professions selected";

            }

            else if (count === 1) {

                selectedCount.textContent =
                    "1 profession selected";

            }

            else {

                selectedCount.textContent =
                    count +
                    " professions selected";

            }

        }


        professionCheckboxes.forEach(
            function (checkbox) {

                checkbox.addEventListener(
                    "change",
                    updateProfessionCount
                );

            }
        );


        updateProfessionCount();


        /* =================================================
           PHONE INPUT
           ================================================= */

        document
            .querySelectorAll(
                "input[type='tel']"
            )
            .forEach(
                function (input) {

                    input.addEventListener(
                        "input",
                        function () {

                            this.value =
                                this.value.replace(
                                    /[^0-9]/g,
                                    ""
                                );

                        }
                    );

                }
            );


        /* =================================================
           INITIAL STATE
           ================================================= */

        <?php if ($role === 'client'): ?>

            showForm("client");

        <?php elseif ($role === 'worker'): ?>

            showForm("worker");

        <?php endif; ?>


    }
);

</script>


</body>

</html>