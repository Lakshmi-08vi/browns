<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
========================================================
AIIMS RAIPUR - HOSPITAL UPKEEP COMPLAINT MANAGEMENT SYSTEM
SINGLE PAGE APPLICATION
========================================================
*/


/* =====================================================
   BASIC CONFIGURATION
===================================================== */

$admin_notification_email = "admin@aiimsraipur.edu.in";


/* =====================================================
   DATA STORAGE HELPERS (FILE BASED - USERS & FEEDBACK)
   Registered users and feedback must survive logout /
   new sessions, so they live in JSON files instead of
   $_SESSION (complaints keep using $_SESSION as before).
===================================================== */

$users_file    = __DIR__ . '/data/users.json';
$feedback_file = __DIR__ . '/data/feedback.json';

function loadJsonData($file) {

    if (!file_exists($file)) {
        return [];
    }

    $raw = @file_get_contents($file);
    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function saveJsonData($file, $data) {

    $dir = dirname($file);

    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    @file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT)
    );
}


/* =====================================================
   LOGOUT
===================================================== */

if (isset($_GET['logout'])) {

    session_destroy();

    header("Location: index.php?page=login");

    exit();
}


/* =====================================================
   DEFAULT PAGE
===================================================== */

$page = isset($_GET['page'])
    ? $_GET['page']
    : 'home';


/* =====================================================
   DEFAULT USER DASHBOARD VIEW
===================================================== */

$user_view = isset($_GET['view'])
    ? $_GET['view']
    : 'dashboard';


/* =====================================================
   DEFAULT ADMIN DASHBOARD VIEW
===================================================== */

$admin_view = isset($_GET['view'])
    ? $_GET['view']
    : 'dashboard';


/* =====================================================
   USER LOGIN (checks real registered accounts)
===================================================== */

$user_login_error = "";

if (isset($_POST['user_login'])) {

    $username = trim($_POST['user_username'] ?? '');
    $password = trim($_POST['user_password'] ?? '');

    $registered_users = loadJsonData($users_file);

    $matched_user = null;

    foreach ($registered_users as $registered) {

        if (
            isset($registered['username']) &&
            strcasecmp($registered['username'], $username) === 0
        ) {
            $matched_user = $registered;
            break;
        }
    }

    if (
        $matched_user !== null &&
        isset($matched_user['password']) &&
        password_verify($password, $matched_user['password'])
    ) {

        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $matched_user['username'];
        $_SESSION['user_name_designation'] = $matched_user['name_designation'] ?? $matched_user['username'];
        $_SESSION['user_email'] = $matched_user['email'] ?? '';
        $_SESSION['user_mobile'] = $matched_user['mobile'] ?? '';

        header("Location: index.php?page=user_dashboard");

        exit();

    } else {

        $user_login_error =
            "Invalid username or password. If you don't have an account yet, please register first.";
    }
}


/* =====================================================
   ADMIN LOGIN
===================================================== */

$admin_login_error = "";
$admin_login_type = "";


/*
========================================================
SEPARATE ADMIN ACCOUNTS
========================================================
*/

$admin_accounts = [

    "Civil" => [
        "username" => "civil",
        "password" => "civil123"
    ],

    "Plumbing" => [
        "username" => "plumbing",
        "password" => "plumbing123"
    ],

    "Electrical" => [
        "username" => "electrical",
        "password" => "electrical123"
    ]

];


if (isset($_POST['admin_login'])) {

    $username =
        trim($_POST['admin_username'] ?? '');

    $password =
        trim($_POST['admin_password'] ?? '');

    $admin_login_type =
        trim($_POST['admin_type'] ?? '');


    if (
        isset($admin_accounts[$admin_login_type]) &&
        $username ===
        $admin_accounts[$admin_login_type]['username'] &&
        $password ===
        $admin_accounts[$admin_login_type]['password']
    ) {

        $_SESSION['admin_logged_in'] = true;

        $_SESSION['admin_username'] =
            $username;

        $_SESSION['admin_type'] =
            $admin_login_type;


        header(
            "Location: index.php?page=admin_dashboard"
        );

        exit();

    } else {

        $admin_login_error =
            "Invalid " .
            $admin_login_type .
            " admin username or password.";
    }
}


/* =====================================================
   REGISTER (creates a real account you can log in with)
===================================================== */

$register_message = "";
$register_success = false;

if (isset($_POST['register_user'])) {

    $reg_username =
        trim($_POST['username'] ?? '');

    $name_designation =
        trim($_POST['name_designation'] ?? '');

    $department =
        trim($_POST['department'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $mobile =
        trim($_POST['mobile'] ?? '');

    $joining_date =
        trim($_POST['joining_date'] ?? '');

    $password =
        trim($_POST['password'] ?? '');

    $confirm_password =
        trim($_POST['confirm_password'] ?? '');


    if (
        $reg_username != "" &&
        $name_designation != "" &&
        $department != "" &&
        $email != "" &&
        $mobile != "" &&
        $joining_date != "" &&
        $password != "" &&
        $confirm_password != ""
    ) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $register_message =
                "Please enter a valid email address.";

        } elseif (strlen($password) < 6) {

            $register_message =
                "Password must be at least 6 characters long.";

        } elseif ($password !== $confirm_password) {

            $register_message =
                "Password and Confirm Password do not match.";

        } else {

            $registered_users = loadJsonData($users_file);

            $username_taken = false;
            $email_taken = false;

            foreach ($registered_users as $registered) {

                if (
                    isset($registered['username']) &&
                    strcasecmp($registered['username'], $reg_username) === 0
                ) {
                    $username_taken = true;
                }

                if (
                    isset($registered['email']) &&
                    strcasecmp($registered['email'], $email) === 0
                ) {
                    $email_taken = true;
                }
            }

            if ($username_taken) {

                $register_message =
                    "This username is already taken. Please choose another username.";

            } elseif ($email_taken) {

                $register_message =
                    "This email is already registered. Please login instead.";

            } else {

                $registered_users[] = [

                    'username' => $reg_username,
                    'name_designation' => $name_designation,
                    'department' => $department,
                    'email' => $email,
                    'mobile' => $mobile,
                    'joining_date' => $joining_date,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'registered_on' => date("d-m-Y h:i A")
                ];

                saveJsonData($users_file, $registered_users);

                $register_message =
                    "Registration successful. You can now login using your username and password.";

                $register_success = true;
            }
        }

    } else {

        $register_message =
            "Please fill all registration details.";
    }
}


/* =====================================================
   WARD / AREA LIST
===================================================== */

$ward_area_list = [

    "1b1 Obgy Ward",
    "1b2 Obgy Ward",
    "1c1 Paediatrics Ward",
    "1c2 Paediatrics Ward",
    "1c3 Nephrology Ward Renal Transplan Ward",

    "2a1 General Surgery Ward",
    "2a2 General Surgery Ward",
    "2a3 Orthopedic Ward",
    "2a4 Orthopedic Ward",
    "2b2 Plastic Surgery Ward",
    "2b4 Surgical Gastroenterology Ward",
    "2c1 Endocrinology Ward",
    "2c4 Medicine Hdu Ward",
    "2d1 Pulmonary Medicine Ward",
    "2d2 Pulmonary Medicine Icu Ward",
    "2d3 Medicine Female Ward",
    "2d4 Medicine Male Ward",

    "3a1 General Surgery Ward",
    "3a2 Medical Oncology Hematology Ward",
    "3a3 Dental Ward & ENT Ward",
    "3a4 Ent Ward",
    "3b1 Neurology Ward",
    "3b2 Neurosurgery Ward",
    "3b3 Cardiology Ward",
    "3b4 Ctvs Ward",
    "3c1 Nephrology Ward",
    "3c2 Urology Ward",
    "3c3 Ophthalmology Ward",
    "3c4 Pediatric Surgery Ward",
    "3d1 Pvt Ward",
    "3d2 Private Ward",
    "3d4 Med Oncology Hematology Day Care",

    "4c1 Cardiology ICCU I",
    "4c2 Cardiology ICCU II",
    "4c3 Neurosurgery ICU Ward",
    "4c4 CTVS ICU Ward",
    "4d1 Medicine ICU Ward",

    "A Block Major OT",
    "Ayush 2nd Floor Gen Ward",
    "Ayush Covid",
    "Ayush Isolation",
    "B Block OT Area",

    "Trauma & Emergency Ground Floor",
    "Trauma ICU 1st Floor",
    "CCU Trauma 2nd Floor",
    "Trauma and Emergency OT 3rd Floor",
    "Pediatric Emergency ward",

    "A Block Minor OT",
    "Covid ICU Ward",
    "Emergency Medicine",
    "Hemodialysis Unit C-Block Basement",
    "NICU",
    "Hemodialysis Unit Trauma-Block 1st Floor",
    "Nuclear Medicine Ward",
    "Obg Labour Room Ward",
    "Obg OT",
    "Blood Bank",

    "Ayurveda OPD",
    "Breast Cancer Clinic OPD",
    "Burns & Plastic Surgery OPD",
    "Cardiology OPD",
    "Cardiothoracic and Vascular Surgery OPD",
    "CFM Immunization (Tikakaran) OPD",
    "Dentistry OPD",
    "Dermatology OPD",
    "Endocrinology OPD",
    "ENT OPD",
    "Gastroenterology and Human Nutrition OPD",
    "Gastrointestinal Surgery OPD",
    "Hematology OPD",
    "Homeopathy OPD",
    "Medical Oncology OPD",
    "Medicine OPD",
    "Nephrology OPD",
    "Neurology OPD",
    "Neurosurgery OPD",
    "Nuclear Medicine Therapy Clinic OPD",
    "Obstetrics and Gynaecology OPD",
    "Ophthalmology OPD",
    "Orthopaediatrics OPD",
    "PAC OPD",
    "Paediatrics OPD",
    "Paediatric Surgery OPD",
    "Pain Clinic OPD",
    "Plastic Surgery OPD",
    "PMR OPD",
    "Psychiatry OPD",
    "Pulmonary Medicine OPD",
    "Radiotherapy OPD",
    "Radiodiagnosis Department",
    "Screening OPD",
    "Siddha OPD",
    "Surgery OPD",
    "Surgical Oncology OPD",
    "Unani OPD",
    "Urology OPD",
    "Yoga OPD",

    "Central Pharmacy",
    "Medical Records Department",
    "Canteen D-Block",
    "Canteen Trauma Block",
    "Dome 1",
    "Dome 2",
    "Dome 3"
];


/* =====================================================
   HELPER - SEND EMAIL
===================================================== */

function sendNotificationEmail(
    $to,
    $subject,
    $message
) {

    $headers =
        "From: AIIMS Raipur Complaint System\r\n" .
        "Reply-To: admin@aiimsraipur.edu.in\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail(
        $to,
        $subject,
        $message,
        $headers
    );
}


/* =====================================================
   USER COMPLAINT
===================================================== */

$complaint_message = "";

if (
    isset($_POST['submit_complaint']) &&
    isset($_SESSION['user_logged_in'])
) {

    $name_designation =
        trim($_POST['name_designation'] ?? '');

    $contact_number =
        trim($_POST['contact_number'] ?? '');

    $complaint_email =
        trim($_POST['complaint_email'] ?? '');

    $department_ward_area =
        trim($_POST['department_ward_area'] ?? '');

    $department_location =
        trim($_POST['department_location'] ?? '');

    $type =
        trim($_POST['complaint_type'] ?? '');

    $complaint_title =
        trim($_POST['complaint_title'] ?? '');

    $description =
        trim($_POST['description'] ?? '');


    if (
        $name_designation != "" &&
        $contact_number != "" &&
        $complaint_email != "" &&
        $department_ward_area != "" &&
        $department_location != "" &&
        $type != "" &&
        $complaint_title != "" &&
        $description != ""
    ) {

        if (!isset($_SESSION['complaints'])) {

            $_SESSION['complaints'] = [];
        }


        /* =================================================
           COMPLAINT ID
        ================================================= */

        $complaint_id =
            "CMP" .
            date("YmdHis") .
            rand(10, 99);


        /* =================================================
           IMAGE UPLOAD
        ================================================= */

        $uploaded_images = [];

        $upload_dir =
            "uploads/complaints/";


        if (!is_dir($upload_dir)) {

            @mkdir(
                $upload_dir,
                0777,
                true
            );
        }


        $allowed_types = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ];


        if (
            isset($_FILES['complaint_images']) &&
            isset($_FILES['complaint_images']['name']) &&
            is_array(
                $_FILES['complaint_images']['name']
            )
        ) {

            $image_count =
                count(
                    $_FILES['complaint_images']['name']
                );


            for (
                $i = 0;
                $i < $image_count;
                $i++
            ) {

                if (
                    $_FILES['complaint_images']['error'][$i]
                    !== UPLOAD_ERR_OK
                ) {

                    continue;
                }


                $original_name =
                    $_FILES['complaint_images']['name'][$i];


                $tmp_name =
                    $_FILES['complaint_images']['tmp_name'][$i];


                $extension =
                    strtolower(
                        pathinfo(
                            $original_name,
                            PATHINFO_EXTENSION
                        )
                    );


                if (
                    !in_array(
                        $extension,
                        $allowed_types
                    )
                ) {

                    continue;
                }


                $new_name =
                    $complaint_id .
                    "_" .
                    uniqid() .
                    "." .
                    $extension;


                $destination =
                    $upload_dir .
                    $new_name;


                if (
                    move_uploaded_file(
                        $tmp_name,
                        $destination
                    )
                ) {

                    $uploaded_images[] =
                        $destination;
                }
            }
        }


        /* =================================================
           SAVE COMPLAINT
        ================================================= */

        $_SESSION['complaints'][] = [

            'id' =>
                $complaint_id,

            'username' =>
                $_SESSION['username'] ?? 'user',

            'name_designation' =>
                $name_designation,

            'contact_number' =>
                $contact_number,

            'email' =>
                $complaint_email,

            'department_ward_area' =>
                $department_ward_area,

            'department_location' =>
                $department_location,

            'type' =>
                $type,

            'complaint_title' =>
                $complaint_title,

            'description' =>
                $description,

            'images' =>
                $uploaded_images,

            'status' =>
                'Pending',

            'assigned_to' =>
                '',

            'assigned_contact' =>
                '',

            'work_process' =>
                '',

            'delay_reason' =>
                '',

            'admin_message' =>
                'Complaint received and is waiting for admin action.',

            'admin_message_date' =>
                date("d-m-Y h:i A"),

            'date' =>
                date("d-m-Y"),

            'time' =>
                date("h:i A"),

            'timestamp' =>
                time()
        ];


        $new_index =
            count($_SESSION['complaints']) - 1;


        /*
        ====================================================
        ADMIN EMAIL NOTIFICATION
        ====================================================
        */

        $admin_subject =
            "New AIIMS Complaint - " .
            $complaint_id;

        $admin_email_message =
            "New complaint has been submitted.\n\n" .

            "Complaint ID: " .
            $complaint_id . "\n" .

            "Name & Designation: " .
            $name_designation . "\n" .

            "Contact: " .
            $contact_number . "\n" .

            "Email: " .
            $complaint_email . "\n" .

            "Department/Ward/Area: " .
            $department_ward_area . "\n" .

            "Location: " .
            $department_location . "\n" .

            "Service Category: " .
            $type . "\n" .

            "Complaint Title: " .
            $complaint_title . "\n\n" .

            "Description:\n" .
            $description . "\n\n" .

            "Login to the AIIMS complaint system to assign and process this complaint.";

        sendNotificationEmail(
            $admin_notification_email,
            $admin_subject,
            $admin_email_message
        );


        /*
        ====================================================
        INTERNAL NOTIFICATION
        ====================================================
        */

        if (!isset($_SESSION['notifications'])) {

            $_SESSION['notifications'] = [];
        }


        $_SESSION['notifications'][] = [

            'type' =>
                'New Complaint',

            'complaint_id' =>
                $complaint_id,

            'message' =>
                "New " .
                $type .
                " complaint submitted by " .
                $name_designation,

            'date' =>
                date("d-m-Y h:i A"),

            'read' =>
                false
        ];


        $complaint_message =
            "Complaint submitted successfully. Complaint ID: "
            . $complaint_id;

    } else {

        $complaint_message =
            "Please fill all complaint details.";
    }
}


/* =====================================================
   ADMIN UPDATE COMPLAINT
===================================================== */

$admin_update_message = "";

if (
    isset($_POST['update_complaint']) &&
    isset($_SESSION['admin_logged_in'])
) {

    $complaint_index =
        intval(
            $_POST['complaint_index'] ?? -1
        );

    $new_status =
        trim($_POST['status'] ?? '');

    $assigned_to =
        trim($_POST['assigned_to'] ?? '');

    $assigned_contact =
        trim($_POST['assigned_contact'] ?? '');

    $work_process =
        trim($_POST['work_process'] ?? '');

    $delay_reason =
        trim($_POST['delay_reason'] ?? '');

    $admin_message =
        trim($_POST['admin_message'] ?? '');


    $allowed_statuses = [

        'Pending',
        'Working',
        'Delayed',
        'Resolved',
        'Closed'

    ];


    if (
        isset(
            $_SESSION['complaints']
            [$complaint_index]
        ) &&
        in_array(
            $new_status,
            $allowed_statuses
        )
    ) {

        /*
        ====================================================
        DELAY VALIDATION
        ====================================================
        */

        if (
            $new_status === "Delayed" &&
            $delay_reason === ""
        ) {

            $admin_update_message =
                "Please enter the reason for delay.";

        } else {

            $_SESSION['complaints']
            [$complaint_index]
            ['status'] =
                $new_status;


            $_SESSION['complaints']
            [$complaint_index]
            ['assigned_to'] =
                $assigned_to;


            $_SESSION['complaints']
            [$complaint_index]
            ['assigned_contact'] =
                $assigned_contact;


            $_SESSION['complaints']
            [$complaint_index]
            ['work_process'] =
                $work_process;


            $_SESSION['complaints']
            [$complaint_index]
            ['delay_reason'] =
                $delay_reason;


            /*
            =================================================
               ADMIN MESSAGE
            =================================================
            */

            if ($admin_message === "") {

                if ($new_status === "Delayed") {

                    $admin_message =
                        "Work is delayed. Reason: " .
                        $delay_reason;

                } elseif ($assigned_to !== "") {

                    $admin_message =
                        "Work assigned to " .
                        $assigned_to .
                        " (" .
                        $assigned_contact .
                        "). Current status: " .
                        $new_status;

                } else {

                    $admin_message =
                        "Complaint status updated to " .
                        $new_status;
                }
            }


            $_SESSION['complaints']
            [$complaint_index]
            ['admin_message'] =
                $admin_message;


            $_SESSION['complaints']
            [$complaint_index]
            ['admin_message_date'] =
                date("d-m-Y h:i A");


            /*
            =================================================
               SEND MESSAGE TO USER EMAIL
            =================================================
            */

            $user_email =
                $_SESSION['complaints']
                [$complaint_index]['email'] ?? '';


            $complaint_id =
                $_SESSION['complaints']
                [$complaint_index]['id'] ?? '';


            if (
                $user_email !== "" &&
                filter_var(
                    $user_email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                $user_subject =
                    "AIIMS Complaint Update - " .
                    $complaint_id;

                $user_email_message =
                    "Your AIIMS Raipur Hospital Upkeep complaint has been updated.\n\n" .

                    "Complaint ID: " .
                    $complaint_id . "\n" .

                    "Status: " .
                    $new_status . "\n" .

                    "Assigned To: " .
                    $assigned_to . "\n" .

                    "Contact: " .
                    $assigned_contact . "\n" .

                    "Work Process: " .
                    $work_process . "\n";

                if (
                    $new_status === "Delayed"
                ) {

                    $user_email_message .=
                        "Delay Reason: " .
                        $delay_reason . "\n";
                }

                $user_email_message .=
                    "\nAdmin Message:\n" .
                    $admin_message . "\n\n" .

                    "Date: " .
                    date("d-m-Y h:i A");


                sendNotificationEmail(
                    $user_email,
                    $user_subject,
                    $user_email_message
                );
            }


            /*
            =================================================
               USER NOTIFICATION
            =================================================
            */

            if (!isset(
                $_SESSION['user_notifications']
            )) {

                $_SESSION['user_notifications'] = [];
            }


            $_SESSION['user_notifications'][] = [

                'complaint_id' =>
                    $complaint_id,

                'message' =>
                    $admin_message,

                'status' =>
                    $new_status,

                'assigned_to' =>
                    $assigned_to,

                'assigned_contact' =>
                    $assigned_contact,

                'date' =>
                    date("d-m-Y h:i A")
            ];


            $admin_update_message =
                "Complaint updated successfully and user notification was generated.";
        }
    }
}


/* =====================================================
   USER FEEDBACK SUBMISSION (rating + message, file based
   so the admin can view it as read-only, across sessions)
===================================================== */

$feedback_message = "";

if (
    isset($_POST['submit_feedback']) &&
    isset($_SESSION['user_logged_in'])
) {

    $rating = intval($_POST['rating'] ?? 0);
    $feedback_text = trim($_POST['feedback_text'] ?? '');

    if ($rating < 1 || $rating > 5 || $feedback_text === "") {

        $feedback_message =
            "Please select a rating and enter your feedback message.";

    } else {

        $all_feedback = loadJsonData($feedback_file);

        $all_feedback[] = [

            'username' => $_SESSION['username'],
            'name' => $_SESSION['user_name_designation'] ?? $_SESSION['username'],
            'rating' => $rating,
            'message' => $feedback_text,
            'date' => date("d-m-Y h:i A")
        ];

        saveJsonData($feedback_file, $all_feedback);

        $feedback_message = "Thank you for your feedback.";
    }
}


/* =====================================================
   HELPER - STATUS COUNT
===================================================== */

function getComplaintCount(
    $status = null,
    $admin_type = null
) {

    $count = 0;


    if (
        isset($_SESSION['complaints']) &&
        is_array($_SESSION['complaints'])
    ) {

        foreach (
            $_SESSION['complaints']
            as $complaint
        ) {

            /*
            ================================================
            ADMIN TYPE FILTER
            ================================================
            */

            if (
                $admin_type !== null &&
                isset($complaint['type']) &&
                strcasecmp(
                    $complaint['type'],
                    $admin_type
                ) !== 0
            ) {

                continue;
            }


            if (
                $status === null ||
                (
                    isset($complaint['status']) &&
                    $complaint['status'] === $status
                )
            ) {

                $count++;
            }
        }
    }


    return $count;
}


/* =====================================================
   GET USER COMPLAINT COUNT
===================================================== */

function getUserComplaintCount(
    $status = null
) {

    $count = 0;


    if (
        isset($_SESSION['complaints']) &&
        is_array($_SESSION['complaints'])
    ) {

        foreach (
            $_SESSION['complaints']
            as $complaint
        ) {

            if (
                !isset($complaint['username']) ||
                $complaint['username']
                !== ($_SESSION['username'] ?? '')
            ) {

                continue;
            }


            if (
                $status === null ||
                (
                    isset($complaint['status']) &&
                    $complaint['status'] === $status
                )
            ) {

                $count++;
            }
        }
    }


    return $count;
}


/* =====================================================
   REPORT PERIOD COUNT
===================================================== */

function getReportCount(
    $period,
    $status = null,
    $admin_type = null,
    $username = null
) {

    $count = 0;

    $today = strtotime(
        date("Y-m-d")
    );


    if (
        !isset($_SESSION['complaints']) ||
        !is_array($_SESSION['complaints'])
    ) {

        return 0;
    }


    foreach (
        $_SESSION['complaints']
        as $complaint
    ) {

        /*
        ================================================
        ADMIN DEPARTMENT FILTER
        ================================================
        */

        if (
            $admin_type !== null &&
            isset($complaint['type']) &&
            strcasecmp(
                $complaint['type'],
                $admin_type
            ) !== 0
        ) {

            continue;
        }


        /*
        ================================================
        USER FILTER
        ================================================
        */

        if (
            $username !== null &&
            (
                !isset($complaint['username']) ||
                $complaint['username'] !== $username
            )
        ) {

            continue;
        }


        if (
            $status !== null &&
            (
                !isset($complaint['status']) ||
                $complaint['status'] !== $status
            )
        ) {

            continue;
        }


        $timestamp =
            isset($complaint['timestamp'])
            ? intval($complaint['timestamp'])
            : 0;


        if ($timestamp <= 0) {

            continue;
        }


        $complaint_day =
            strtotime(
                date(
                    "Y-m-d",
                    $timestamp
                )
            );


        /*
        ================================================
        DAILY
        ================================================
        */

        if ($period === "daily") {

            if (
                $complaint_day ==
                $today
            ) {

                $count++;
            }
        }


        /*
        ================================================
        WEEKLY
        ================================================
        */

        if ($period === "weekly") {

            $week_start =
                strtotime(
                    "monday this week"
                );

            $week_end =
                strtotime(
                    "sunday this week 23:59:59"
                );


            if (
                $timestamp >= $week_start &&
                $timestamp <= $week_end
            ) {

                $count++;
            }
        }


        /*
        ================================================
        MONTHLY
        ================================================
        */

        if ($period === "monthly") {

            if (
                date("Y-m", $timestamp)
                === date("Y-m")
            ) {

                $count++;
            }
        }
    }


    return $count;
}


/* =====================================================
   REPORT PERCENTAGE
===================================================== */

function getPercentage(
    $value,
    $total
) {

    if ($total <= 0) {

        return 0;
    }


    return round(
        ($value / $total) * 100,
        1
    );
}


/* =====================================================
   STATUS CIRCLE
===================================================== */

function statusCircle(
    $label,
    $count,
    $total,
    $class
) {

    $percentage =
        getPercentage(
            $count,
            $total
        );

?>

<div class="circle-report">

    <div
        class="circle <?php echo $class; ?>"
        style="
        --percentage:<?php
        echo $percentage;
        ?>;
        "
    >

        <div class="circle-inner">

            <strong>
                <?php
                echo $percentage;
                ?>%
            </strong>

            <small>
                <?php
                echo $count;
                ?>
            </small>

        </div>

    </div>

    <h4>
        <?php
        echo htmlspecialchars($label);
        ?>
    </h4>

</div>

<?php
}


/* =====================================================
   HTML START
===================================================== */

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
AIIMS Raipur - Hospital Upkeep Complaint Management System
</title>


<style>

/* =====================================================
   GENERAL
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f1f7fc;

    color: #16385f;
}


/* =====================================================
   HEADER
===================================================== */

.header {

    background: white;

    min-height: 145px;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px 30px;

    border-bottom: 4px solid #075b9d;
}


.header-inner {

    display: flex;

    align-items: center;

    gap: 25px;

    width: 100%;

    max-width: 1250px;
}


.logo {

    width: 105px;

    height: 105px;

    object-fit: contain;

    border-radius: 50%;
}


.title-area {

    text-align: left;
}


.title-area h1 {

    color: #123f70;

    font-size: 29px;

    margin-bottom: 8px;
}


.title-area h2 {

    color: #19558e;

    font-size: 22px;

    margin-bottom: 8px;
}


.title-area h3 {

    color: #075b9d;

    font-size: 20px;

    font-weight: bold;
}


/* =====================================================
   NAVIGATION
===================================================== */

.navbar {

    background: #075b9d;

    min-height: 55px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 5px 25px;

    flex-wrap: wrap;
}


.nav-left,
.nav-right {

    display: flex;

    align-items: center;

    gap: 5px;

    flex-wrap: wrap;
}


.navbar a {

    color: white;

    text-decoration: none;

    padding: 14px 20px;

    font-size: 16px;

    font-weight: bold;

    border-radius: 5px;
}


.navbar a:hover,
.navbar a.active {

    background: white;

    color: #075b9d;
}


/* =====================================================
   HERO
===================================================== */

.hero {

    min-height: 430px;

    position: relative;

    overflow: hidden;

    background: #dceffd;
}


.hero-image {

    position: absolute;

    right: 0;

    top: 0;

    width: 62%;

    height: 100%;

    background-image:

        linear-gradient(
            90deg,
            rgba(220,239,253,1) 0%,
            rgba(220,239,253,.7) 18%,
            rgba(220,239,253,0) 55%
        ),

        url("assets/hospital-building.jpg");

    background-size: cover;

    background-position: center;
}


.hero-content {

    position: relative;

    z-index: 2;

    width: 50%;

    padding: 75px 0 50px 8%;
}


.welcome {

    color: #219653;

    font-size: 25px;

    font-weight: bold;

    margin-bottom: 12px;
}


.hero-content h2 {

    color: #123f70;

    font-size: 45px;

    line-height: 1.15;

    margin-bottom: 20px;
}


.green-line {

    width: 70px;

    height: 4px;

    background: #219653;

    margin-bottom: 20px;
}


.hero-content p {

    font-size: 18px;

    line-height: 1.7;

    max-width: 570px;
}


/* =====================================================
   MAIN CONTENT
===================================================== */

.container {

    width: 90%;

    max-width: 1250px;

    margin: 40px auto;
}


.content-box {

    background: white;

    padding: 35px;

    border-radius: 10px;

    box-shadow:
        0 4px 15px rgba(0,0,0,.10);

    margin-bottom: 30px;
}


.content-box h2 {

    color: #075b9d;

    margin-bottom: 20px;

    border-bottom: 3px solid #075b9d;

    padding-bottom: 10px;
}


.content-box h3 {

    color: #1765a8;

    margin: 20px 0 10px;
}


.content-box p {

    color: #444;

    line-height: 1.8;

    margin-bottom: 15px;

    text-align: justify;
}


/* =====================================================
   CARDS
===================================================== */

.cards {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 25px;
}


.card {

    background: white;

    padding: 30px;

    border-radius: 10px;

    text-align: center;

    border-top: 5px solid #075b9d;

    box-shadow:
        0 4px 15px rgba(0,0,0,.10);
}


.card h3 {

    color: #075b9d;

    margin-bottom: 15px;
}


.card p {

    color: #555;

    line-height: 1.6;

    font-size: 30px;

    font-weight: bold;
}


/* =====================================================
   REPORT CARDS
===================================================== */

.report-cards {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

    margin-top: 20px;
}


.report-card {

    background: #fff;

    padding: 25px;

    border-radius: 10px;

    text-align: center;

    box-shadow:
        0 3px 12px rgba(0,0,0,.10);

    border-left: 5px solid #075b9d;
}


.report-card h3 {

    color: #075b9d;

    margin-bottom: 12px;
}


.report-number {

    font-size: 35px;

    font-weight: bold;

    color: #16385f;
}


/* =====================================================
   CIRCULAR REPORT
===================================================== */

.circular-report-box {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 25px;

    margin-top: 25px;
}


.circle-report {

    background: white;

    padding: 25px;

    border-radius: 12px;

    text-align: center;

    box-shadow:
        0 4px 15px rgba(0,0,0,.10);
}


.circle-report h4 {

    margin-top: 15px;

    font-size: 18px;

    color: #16385f;
}


.circle {

    width: 180px;

    height: 180px;

    border-radius: 50%;

    margin: auto;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        conic-gradient(
            var(--circle-color)
            calc(var(--percentage) * 1%),
            #e7e7e7
            0
        );
}


.circle-inner {

    width: 125px;

    height: 125px;

    border-radius: 50%;

    background: white;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;
}


.circle-inner strong {

    font-size: 25px;

    color: #16385f;
}


.circle-inner small {

    font-size: 15px;

    color: #666;

    margin-top: 5px;
}


.circle-pending {

    --circle-color: #f4b400;
}


.circle-working {

    --circle-color: #1976d2;
}


.circle-delayed {

    --circle-color: #e53935;
}


.circle-resolved {

    --circle-color: #198754;
}


.circle-closed {

    --circle-color: #6f42c1;
}


/* =====================================================
   REPORT PERIOD
===================================================== */

.report-period {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;

    margin-bottom: 25px;
}


.report-period a {

    display: inline-block;

    padding: 12px 20px;

    background: #075b9d;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    font-weight: bold;
}


.report-period a:hover {

    background: #043e6d;
}


/* =====================================================
   COMPLAINT TYPES
===================================================== */

.complaint-types {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 25px;
}


.complaint-type {

    padding: 35px;

    text-align: center;

    background: #e9f5ff;

    border: 2px solid #1976bd;

    border-radius: 10px;

    font-size: 22px;

    font-weight: bold;

    color: #075b9d;
}


/* =====================================================
   FORM
===================================================== */

.form-box {

    max-width: 850px;

    margin: auto;

    background: white;

    padding: 35px;

    border-radius: 10px;

    box-shadow:
        0 4px 15px rgba(0,0,0,.10);
}


.form-box label {

    display: block;

    font-weight: bold;

    margin-top: 15px;

    margin-bottom: 7px;
}


.form-box input,
.form-box select,
.form-box textarea {

    width: 100%;

    padding: 12px;

    border: 1px solid #bbb;

    border-radius: 5px;

    font-size: 15px;

    background: white;
}


.form-box textarea {

    height: 120px;

    resize: vertical;
}


.form-box input[type="file"] {

    padding: 10px;

    background: #f8fbff;
}


.btn {

    display: inline-block;

    margin-top: 20px;

    padding: 13px 25px;

    border: none;

    border-radius: 5px;

    background: #075b9d;

    color: white;

    text-decoration: none;

    cursor: pointer;

    font-weight: bold;
}


.btn:hover {

    background: #043e6d;
}


.green-btn {

    background: #198754;
}


.red-btn {

    background: #dc3545;
}


/* =====================================================
   STAR RATING (feedback)
===================================================== */

.rating-group {

    display: flex;

    flex-direction: row-reverse;

    justify-content: flex-end;

    gap: 6px;

    font-size: 34px;

    margin-bottom: 10px;
}


.rating-group input {

    display: none;
}


.rating-group .star-label {

    color: #ccc;

    cursor: pointer;

    margin: 0;

    padding: 0;
    width: auto;
}


.rating-group input:checked ~ .star-label,
.rating-group .star-label:hover,
.rating-group .star-label:hover ~ .star-label {

    color: #f4b400;
}


/* =====================================================
   LOGIN
===================================================== */

.login-options {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 30px;

    max-width: 1000px;

    margin: auto;
}


.login-option {

    background: white;

    padding: 40px;

    text-align: center;

    border-radius: 10px;

    box-shadow:
        0 4px 15px rgba(0,0,0,.12);

    border-top: 5px solid #075b9d;
}


.login-option h3 {

    color: #075b9d;

    margin-bottom: 15px;

    font-size: 25px;
}


/* =====================================================
   DASHBOARD LAYOUT
===================================================== */

.dashboard-layout {

    display: grid;

    grid-template-columns: 250px 1fr;

    gap: 25px;

    align-items: start;
}


.dashboard-sidebar {

    background: #075b9d;

    padding: 15px;

    border-radius: 10px;

    position: sticky;

    top: 20px;
}


.dashboard-sidebar h3 {

    color: white;

    padding: 15px;

    border-bottom: 1px solid rgba(255,255,255,.3);

    margin-bottom: 10px;
}


.dashboard-sidebar a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 14px;

    margin-bottom: 6px;

    border-radius: 6px;

    font-weight: bold;
}


.dashboard-sidebar a:hover,
.dashboard-sidebar a.active {

    background: white;

    color: #075b9d;
}


.dashboard-content {

    min-width: 0;
}


.dashboard-title {

    background: #075b9d;

    color: white;

    padding: 20px;

    border-radius: 8px;

    margin-bottom: 20px;
}


.dashboard-title h2 {

    margin-bottom: 8px;
}


/* =====================================================
   TABLE
===================================================== */

.table-container {

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

    background: white;
}


th {

    background: #075b9d;

    color: white;

    padding: 13px;

    text-align: left;
}


td {

    padding: 12px;

    border: 1px solid #ddd;

    vertical-align: top;
}


.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 4px;

    font-weight: bold;

    background: #e8f1f8;
}


.status-Pending {

    background: #fff3cd;
    color: #856404;
}


.status-Working {

    background: #cfe2ff;
    color: #084298;
}


.status-Delayed {

    background: #f8d7da;
    color: #842029;
}


.status-Resolved {

    background: #d1e7dd;
    color: #0f5132;
}


.status-Closed {

    background: #e2d9f3;
    color: #432874;
}


/* =====================================================
   PHOTO PREVIEW
===================================================== */

.photo-grid {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;
}


.photo-grid img {

    width: 80px;

    height: 80px;

    object-fit: cover;

    border-radius: 6px;

    border: 1px solid #ccc;
}


/* =====================================================
   MESSAGE
===================================================== */

.message {

    padding: 15px;

    background: #dff5e7;

    border-left: 5px solid #198754;

    margin-bottom: 20px;

    color: #155724;
}


.error {

    background: #ffe0e0;

    border-left-color: #dc3545;

    color: #721c24;
}


/* =====================================================
   NOTIFICATION
===================================================== */

.notification {

    background: #fff8dc;

    border-left: 5px solid #f4b400;

    padding: 15px;

    margin-bottom: 15px;

    border-radius: 5px;
}


.assignment-box {

    background: #f4f9ff;

    border: 1px solid #c9dff2;

    padding: 15px;

    border-radius: 8px;

    min-width: 280px;
}


/* =====================================================
   CONTACT
===================================================== */

.contact-list {

    list-style: none;
}


.contact-list li {

    padding: 14px;

    border-bottom: 1px solid #ddd;

    font-size: 17px;
}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    background: #06366d;

    color: white;

    text-align: center;

    padding: 25px;

    margin-top: 50px;

    line-height: 1.8;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:800px) {

    .header-inner {

        flex-direction: column;

        text-align: center;
    }


    .title-area {

        text-align: center;
    }


    .title-area h1 {

        font-size: 22px;
    }


    .title-area h2 {

        font-size: 18px;
    }


    .title-area h3 {

        font-size: 16px;
    }


    .navbar {

        justify-content: center;
    }


    .nav-left,
    .nav-right {

        justify-content: center;

        width: 100%;
    }


    .hero-image {

        width: 100%;

        opacity: .25;
    }


    .hero-content {

        width: 100%;

        padding: 60px 25px;
    }


    .hero-content h2 {

        font-size: 34px;
    }


    .cards,
    .complaint-types,
    .login-options,
    .report-cards,
    .circular-report-box {

        grid-template-columns: 1fr;
    }


    .dashboard-layout {

        grid-template-columns: 1fr;
    }


    .dashboard-sidebar {

        position: static;
    }


    .navbar a {

        padding: 10px 12px;

        font-size: 14px;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

<div class="header-inner">

<img
    src="assets/aiims_logo.png"
    alt="AIIMS Hospital Logo"
    class="logo"
>


<div class="title-area">

<h1>
ALL INDIA INSTITUTE OF MEDICAL SCIENCES
</h1>

<h2>
AIIMS RAIPUR - CHHATTISGARH
</h2>

<h3>
HOSPITAL UPKEEP COMPLAINT MANAGEMENT SYSTEM
</h3>

</div>

</div>

</header>


<!-- =====================================================
     NAVIGATION
===================================================== -->

<nav class="navbar">

<div class="nav-left">

<a href="index.php?page=home">
HOME
</a>

<a href="index.php?page=about">
ABOUT
</a>

<a href="index.php?page=contact">
CONTACT
</a>

<a href="index.php?page=complaint">
COMPLAINT
</a>

</div>


<div class="nav-right">

<a href="index.php?page=login">
LOGIN
</a>

<a href="index.php?page=register">
REGISTER
</a>

</div>

</nav>


<!-- =====================================================
     HERO
===================================================== -->

<?php if ($page == "home") { ?>

<section class="hero">

<div class="hero-image"></div>

<div class="hero-content">

<div class="welcome">
Welcome to AIIMS Raipur
</div>

<h2>
Hospital Upkeep<br>
Complaint Management System
</h2>

<div class="green-line"></div>

<p>
A digital platform designed to support
efficient hospital upkeep, maintenance
reporting and complaint management.
</p>

</div>

</section>

<?php } ?>


<main class="container">


<!-- =====================================================
     HOME
===================================================== -->

<?php if ($page == "home") { ?>

<div class="content-box">

<h2>
Home - AIIMS Raipur
</h2>

<p>
All India Institute of Medical Sciences, Raipur, commonly
known as AIIMS Raipur, is a premier healthcare and medical
education institution located in Raipur, Chhattisgarh.
The institute serves the people of Chhattisgarh and patients
from neighbouring regions by providing a wide range of
medical services, specialised treatment, education and
research facilities.
</p>

<p>
The Hospital Upkeep Complaint Management System is designed
to support the smooth functioning and maintenance of hospital
facilities. A hospital requires a clean, safe, functional and
well-maintained environment for patients, visitors, doctors,
nurses, technicians and other staff members.
</p>

<p>
The system provides a structured way of identifying upkeep
and maintenance-related problems. Issues related to civil
maintenance, plumbing and electrical facilities can be
identified and managed through the complaint management
process.
</p>

<p>
Users who are registered with the system can access their
dashboard after successful login. From the user dashboard,
the user can register a complaint, track complaints, view
complaint information, view reports and receive administrator
updates.
</p>

</div>

<?php } ?>


<!-- =====================================================
     ABOUT
===================================================== -->

<?php if ($page == "about") { ?>

<div class="content-box">

<h2>
About AIIMS Raipur
</h2>

<p>
All India Institute of Medical Sciences, Raipur is a
premier medical institution located in Raipur, Chhattisgarh.
AIIMS institutions are established to provide high-quality
healthcare services while also supporting medical education,
training and research.
</p>

<p>
A hospital environment requires continuous maintenance.
Buildings, electrical systems, plumbing facilities and
other infrastructure need regular monitoring and timely
attention.
</p>

<p>
The Hospital Upkeep Complaint Management System provides
a digital method for recording and managing upkeep-related
complaints. The system focuses on Civil, Plumbing and
Electrical complaints.
</p>

</div>

<?php } ?>


<!-- =====================================================
     CONTACT
===================================================== -->

<?php if ($page == "contact") { ?>

<div class="content-box">

<h2>
AIIMS Raipur Contact Information
</h2>

<ul class="contact-list">

<li>
<strong>Hospital Name:</strong>
All India Institute of Medical Sciences, Raipur
</li>

<li>
<strong>Location:</strong>
Tatibandh, G.E. Road, Raipur, Chhattisgarh
</li>

<li>
<strong>PIN Code:</strong>
492099
</li>

<li>
<strong>Hospital Enquiry:</strong>
0771-2572240
</li>

<li>
<strong>Emergency Service:</strong>
0771-2577293
</li>

<li>
<strong>Email:</strong>
admin@aiimsraipur.edu.in
</li>

</ul>

</div>

<?php } ?>


<!-- =====================================================
     COMPLAINT INFORMATION
===================================================== -->

<?php if ($page == "complaint") { ?>

<div class="content-box">

<h2>
Hospital Upkeep Complaint Types
</h2>

<p>
The Hospital Upkeep Complaint Management System currently
covers the following maintenance categories:
</p>

<div class="complaint-types">

<div class="complaint-type">
CIVIL
</div>

<div class="complaint-type">
PLUMBING
</div>

<div class="complaint-type">
ELECTRICAL
</div>

</div>

</div>

<?php } ?>


<!-- =====================================================
     LOGIN OPTIONS
===================================================== -->

<?php if ($page == "login") { ?>

<div class="content-box">

<h2>
Login
</h2>

<div class="login-options"
style="grid-template-columns:repeat(2,1fr);max-width:800px;">

<div class="login-option">

<h3>
User Login
</h3>

<p>
Login as a registered hospital user.
</p>

<a
href="index.php?page=user_login"
class="btn"
>
USER LOGIN
</a>

</div>


<div class="login-option">

<h3>
Admin Login
</h3>

<p>
Login as hospital administrator.
</p>

<a
href="index.php?page=admin_login"
class="btn"
>
ADMIN LOGIN
</a>

</div>

</div>

</div>

<?php } ?>


<!-- =====================================================
     USER LOGIN
===================================================== -->

<?php if ($page == "user_login") { ?>

<div class="content-box">

<h2>
User Login
</h2>

<?php if ($user_login_error != "") { ?>

<div class="message error">

<?php
echo htmlspecialchars(
    $user_login_error
);
?>

</div>

<?php } ?>


<div class="form-box">

<form method="POST">

<label>
Username
</label>

<input
type="text"
name="user_username"
required
>


<label>
Password
</label>

<input
type="password"
name="user_password"
required
>


<button
type="submit"
name="user_login"
class="btn"
>
LOGIN
</button>

<br>

<a
href="index.php?page=forgot_password"
class="btn"
>
FORGOT PASSWORD
</a>

<a
href="index.php?page=register"
class="btn green-btn"
>
NEW USER? REGISTER
</a>

</form>

</div>

</div>

<?php } ?>


<!-- =====================================================
     ADMIN TYPE SELECTION
===================================================== -->

<?php if ($page == "admin_login") { ?>

<div class="content-box">

<h2>
Admin Login
</h2>

<p style="text-align:center;">
Select the maintenance department administrator.
</p>


<div class="login-options">


<div class="login-option">

<h3>
Civil Admin
</h3>

<p>
Civil maintenance complaints.
</p>

<a
href="index.php?page=civil_admin_login"
class="btn"
>
CIVIL ADMIN LOGIN
</a>

</div>


<div class="login-option">

<h3>
Plumbing Admin
</h3>

<p>
Plumbing maintenance complaints.
</p>

<a
href="index.php?page=plumbing_admin_login"
class="btn"
>
PLUMBING ADMIN LOGIN
</a>

</div>


<div class="login-option">

<h3>
Electrical Admin
</h3>

<p>
Electrical maintenance complaints.
</p>

<a
href="index.php?page=electrical_admin_login"
class="btn"
>
ELECTRICAL ADMIN LOGIN
</a>

</div>


</div>

<br>

<div style="text-align:center;">

<a
href="index.php?page=login"
class="btn"
>
BACK TO LOGIN
</a>

</div>

</div>

<?php } ?>


<!-- =====================================================
     CIVIL ADMIN LOGIN
===================================================== -->

<?php if ($page == "civil_admin_login") { ?>

<div class="content-box">

<h2>
Civil Admin Login
</h2>


<?php

if (
    $admin_login_error != "" &&
    $admin_login_type == "Civil"
) {

?>

<div class="message error">

<?php
echo htmlspecialchars(
    $admin_login_error
);
?>

</div>

<?php } ?>


<div class="form-box">

<form method="POST">

<input
type="hidden"
name="admin_type"
value="Civil"
>


<label>
Civil Admin Username
</label>

<input
type="text"
name="admin_username"
required
>


<label>
Password
</label>

<input
type="password"
name="admin_password"
required
>


<button
type="submit"
name="admin_login"
class="btn"
>
LOGIN AS CIVIL ADMIN
</button>

</form>

<br>

<a
href="index.php?page=admin_login"
class="btn"
>
BACK
</a>

</div>

</div>

<?php } ?>


<!-- =====================================================
     PLUMBING ADMIN LOGIN
===================================================== -->

<?php if ($page == "plumbing_admin_login") { ?>

<div class="content-box">

<h2>
Plumbing Admin Login
</h2>


<?php

if (
    $admin_login_error != "" &&
    $admin_login_type == "Plumbing"
) {

?>

<div class="message error">

<?php
echo htmlspecialchars(
    $admin_login_error
);
?>

</div>

<?php } ?>


<div class="form-box">

<form method="POST">

<input
type="hidden"
name="admin_type"
value="Plumbing"
>


<label>
Plumbing Admin Username
</label>

<input
type="text"
name="admin_username"
required
>


<label>
Password
</label>

<input
type="password"
name="admin_password"
required
>


<button
type="submit"
name="admin_login"
class="btn"
>
LOGIN AS PLUMBING ADMIN
</button>

</form>

<br>

<a
href="index.php?page=admin_login"
class="btn"
>
BACK
</a>

</div>

</div>

<?php } ?>


<!-- =====================================================
     ELECTRICAL ADMIN LOGIN
===================================================== -->

<?php if ($page == "electrical_admin_login") { ?>

<div class="content-box">

<h2>
Electrical Admin Login
</h2>


<?php

if (
    $admin_login_error != "" &&
    $admin_login_type == "Electrical"
) {

?>

<div class="message error">

<?php
echo htmlspecialchars(
    $admin_login_error
);
?>

</div>

<?php } ?>


<div class="form-box">

<form method="POST">

<input
type="hidden"
name="admin_type"
value="Electrical"
>


<label>
Electrical Admin Username
</label>

<input
type="text"
name="admin_username"
required
>


<label>
Password
</label>

<input
type="password"
name="admin_password"
required
>


<button
type="submit"
name="admin_login"
class="btn"
>
LOGIN AS ELECTRICAL ADMIN
</button>

</form>

<br>

<a
href="index.php?page=admin_login"
class="btn"
>
BACK
</a>

</div>

</div>

<?php } ?>


<!-- =====================================================
     FORGOT PASSWORD
===================================================== -->

<?php if ($page == "forgot_password") { ?>

<div class="content-box">

<h2>
Forgot Password
</h2>

<div class="form-box">

<form>

<label>
Email ID
</label>

<input
type="email"
placeholder="Enter registered email"
required
>

<button
type="submit"
class="btn"
>
SEND OTP
</button>

</form>

<br>

<a
href="index.php?page=login"
class="btn"
>
BACK TO LOGIN
</a>

</div>

</div>

<?php } ?>


<!-- =====================================================
     REGISTER
===================================================== -->

<?php if ($page == "register") { ?>

<div class="content-box">

<h2>
Create New Account
</h2>

<?php if ($register_message != "") { ?>

<div class="message<?php echo $register_success ? '' : ' error'; ?>">
<?php
echo htmlspecialchars(
    $register_message
);
?>
</div>

<?php } ?>


<?php if ($register_success) { ?>

<div class="form-box" style="text-align:center;">

<p style="margin-bottom:20px;">
Your account has been created. Login is now available only
using the username and password you just registered.
</p>

<a
href="index.php?page=user_login"
class="btn"
>
GO TO LOGIN
</a>

</div>

<?php } else { ?>

<div class="form-box">

<form method="POST">

<label>
Choose a Username
</label>

<input
type="text"
name="username"
required
>


<label>
Name & Designation
</label>

<input
type="text"
name="name_designation"
required
>


<label>
Department
</label>

<input
type="text"
name="department"
required
>


<label>
Email ID
</label>

<input
type="email"
name="email"
required
>


<label>
Mobile Number
</label>

<input
type="tel"
name="mobile"
pattern="[0-9]{10}"
maxlength="10"
required
>


<label>
Joining Date
</label>

<input
type="date"
name="joining_date"
required
>

<label>
Password
</label>

<input
type="password"
name="password"
required
>

<label>
Confirm Password
</label>

<input
type="password"
name="confirm_password"
required
>


<button
type="submit"
name="register_user"
class="btn green-btn"
>
REGISTER
</button>

</form>

</div>

<?php } ?>

</div>

<?php } ?>


<!-- =====================================================
     USER DASHBOARD
===================================================== -->

<?php if ($page == "user_dashboard") { ?>

<?php

if (
    !isset($_SESSION['user_logged_in'])
) {

    header(
        "Location: index.php?page=user_login"
    );

    exit();
}

?>


<div class="dashboard-layout">


<div class="dashboard-sidebar">

<h3>
User Dashboard
</h3>

<a
href="index.php?page=user_dashboard&view=dashboard"
class="<?php
echo $user_view == 'dashboard'
? 'active'
: '';
?>"
>
Dashboard
</a>

<a
href="index.php?page=user_dashboard&view=register_complaint"
class="<?php
echo $user_view == 'register_complaint'
? 'active'
: '';
?>"
>
Register Complaint
</a>

<a
href="index.php?page=user_dashboard&view=track_complaint"
class="<?php
echo $user_view == 'track_complaint'
? 'active'
: '';
?>"
>
Track Complaint
</a>

<a
href="index.php?page=user_dashboard&view=complaints"
class="<?php
echo $user_view == 'complaints'
? 'active'
: '';
?>"
>
Complaints
</a>

<a
href="index.php?page=user_dashboard&view=reports"
class="<?php
echo $user_view == 'reports'
? 'active'
: '';
?>"
>
Reports
</a>

<a
href="index.php?page=user_dashboard&view=feedback"
class="<?php
echo $user_view == 'feedback'
? 'active'
: '';
?>"
>
Feedback
</a>

<a href="index.php?logout=1">
Logout
</a>

</div>


<div class="dashboard-content">

<div class="dashboard-title">

<h2>
User Dashboard
</h2>

<p>
Welcome,
<?php
echo htmlspecialchars(
    $_SESSION['user_name_designation'] ?? $_SESSION['username']
);
?>
</p>

</div>


<!-- =====================================================
     USER DASHBOARD
===================================================== -->

<?php if ($user_view == "dashboard") { ?>

<div class="content-box">

<h2>
Dashboard Overview
</h2>

<p>
Use the menu to register complaints, track complaints,
view reports and receive administrator work updates.
</p>


<div class="report-cards">

<div class="report-card">

<h3>
Total Complaints
</h3>

<div class="report-number">
<?php
echo getUserComplaintCount();
?>
</div>

</div>


<div class="report-card">

<h3>
Pending
</h3>

<div class="report-number">
<?php
echo getUserComplaintCount('Pending');
?>
</div>

</div>


<div class="report-card">

<h3>
Working
</h3>

<div class="report-number">
<?php
echo getUserComplaintCount('Working');
?>
</div>

</div>

</div>

</div>

<?php } ?>


<!-- =====================================================
     REGISTER COMPLAINT
===================================================== -->

<?php if ($user_view == "register_complaint") { ?>

<div class="content-box">

<h2>
Register Complaint
</h2>


<?php if ($complaint_message != "") { ?>

<div class="message">

<?php
echo htmlspecialchars(
    $complaint_message
);
?>

</div>

<?php } ?>


<div class="form-box">

<form
method="POST"
enctype="multipart/form-data"
>


<h3 style="
border-left:5px solid #075b9d;
padding:12px 15px;
background:#eef7ff;
margin-bottom:20px;
">
Complaint Raised By
</h3>


<label>
Name & Designation
</label>

<input
type="text"
name="name_designation"
value="<?php
echo htmlspecialchars(
    $_SESSION['user_name_designation'] ?? $_SESSION['username']
);
?>"
required
>


<label>
Contact Number
</label>

<input
type="tel"
name="contact_number"
pattern="[0-9]{10}"
maxlength="10"
value="<?php
echo htmlspecialchars(
    $_SESSION['user_mobile'] ?? ''
);
?>"
required
>


<label>
Email
</label>

<input
type="email"
name="complaint_email"
value="<?php
echo htmlspecialchars(
    $_SESSION['user_email'] ?? ''
);
?>"
required
>


<label>
Department / Ward / Area
</label>

<select
name="department_ward_area"
required
>

<option value="">
-- Select Department / Ward / Area --
</option>


<optgroup label="Hospital Departments">

<option>Administration</option>
<option>Cardiology</option>
<option>General Medicine</option>
<option>General Surgery</option>
<option>Orthopaedics</option>
<option>Paediatrics</option>
<option>Obstetrics and Gynaecology</option>
<option>ENT</option>
<option>Ophthalmology</option>
<option>Dermatology</option>
<option>Psychiatry</option>
<option>Radiology</option>
<option>Pathology</option>
<option>Microbiology</option>
<option>Emergency</option>
<option>Laboratory</option>
<option>Pharmacy</option>
<option>Blood Bank</option>
<option>Operation Theatre</option>
<option>ICU</option>

</optgroup>


<optgroup label="Wards">

<?php

foreach (
    $ward_area_list
    as $ward
) {

?>

<option value="<?php
echo htmlspecialchars($ward);
?>">

<?php
echo htmlspecialchars($ward);
?>

</option>

<?php

}

?>

</optgroup>


<optgroup label="Hospital Areas / Facilities">

<option>OPD</option>
<option>IPD</option>
<option>Registration Counter</option>
<option>Waiting Area</option>
<option>Reception</option>
<option>Corridor</option>
<option>Staircase</option>
<option>Lift Area</option>
<option>Toilet</option>
<option>Washroom</option>
<option>Parking Area</option>
<option>Canteen</option>
<option>Store</option>
<option>Mortuary</option>
<option>Other Hospital Area</option>

</optgroup>

</select>


<label>
Department Location: Block & Floor
</label>

<input
type="text"
name="department_location"
placeholder="Example: Block A - 2nd Floor"
required
>


<h3 style="
border-left:5px solid #075b9d;
padding:12px 15px;
background:#eef7ff;
margin-top:30px;
margin-bottom:20px;
">
Complaint Details
</h3>


<label>
Service Category
</label>

<select
name="complaint_type"
required
>

<option value="">
-- Select Service Category --
</option>

<option value="Civil">
Civil
</option>

<option value="Plumbing">
Plumbing
</option>

<option value="Electrical">
Electrical
</option>

<option value="Other">
Other
</option>

</select>


<label>
Complaint Title
</label>

<input
type="text"
name="complaint_title"
required
>


<label>
Complaint Description
</label>

<textarea
name="description"
required
></textarea>


<label>
Upload Complaint Pictures
</label>

<input
type="file"
name="complaint_images[]"
accept="image/jpeg,image/png,image/jpg,image/webp"
multiple
>


<small>
You can select multiple pictures.
</small>


<button
type="submit"
name="submit_complaint"
class="btn"
>
SUBMIT COMPLAINT
</button>


<a
href="index.php?page=user_dashboard"
class="btn"
>
BACK TO DASHBOARD
</a>

</form>

</div>

</div>

<?php } ?>


<!-- =====================================================
     TRACK COMPLAINT
===================================================== -->

<?php if ($user_view == "track_complaint") { ?>

<div class="content-box">

<h2>
Track Complaint
</h2>


<div class="table-container">

<table>

<tr>

<th>Complaint ID</th>
<th>Service</th>
<th>Location</th>
<th>Title</th>
<th>Status</th>
<th>Assigned To</th>
<th>Work Process</th>
<th>Admin Message</th>
<th>Date</th>

</tr>


<?php

$found = false;


if (
    isset($_SESSION['complaints'])
) {

foreach (
    $_SESSION['complaints']
    as $complaint
) {

if (
    ($complaint['username'] ?? '')
    !== $_SESSION['username']
) {

continue;

}


$found = true;

?>


<tr>

<td>
<?php
echo htmlspecialchars(
    $complaint['id'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['type'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['department_location']
    ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['complaint_title']
    ?? ''
);
?>
</td>

<td>

<span class="status">

<?php
echo htmlspecialchars(
    $complaint['status'] ?? ''
);
?>

</span>

</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['assigned_to'] ?? ''
);
?>

<br>

<?php
echo htmlspecialchars(
    $complaint['assigned_contact'] ?? ''
);
?>

</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['work_process'] ?? ''
);
?>
</td>

<td>

<?php
echo htmlspecialchars(
    $complaint['admin_message'] ?? ''
);
?>

<br>

<small>
<?php
echo htmlspecialchars(
    $complaint['admin_message_date'] ?? ''
);
?>
</small>

</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['date'] ?? ''
);
?>
</td>

</tr>


<?php

}

}


if (!$found) {

?>

<tr>

<td colspan="9">
No complaints found.
</td>

</tr>

<?php

}

?>

</table>

</div>

</div>

<?php } ?>


<!-- =====================================================
     USER COMPLAINTS
===================================================== -->

<?php if ($user_view == "complaints") { ?>

<div class="content-box">

<h2>
My Complaints
</h2>


<div class="table-container">

<table>

<tr>

<th>Complaint ID</th>
<th>Raised By</th>
<th>Contact</th>
<th>Department / Ward / Area</th>
<th>Location</th>
<th>Service</th>
<th>Title</th>
<th>Description</th>
<th>Pictures</th>
<th>Status</th>
<th>Assigned Worker</th>
<th>Work Process</th>
<th>Admin Message</th>

</tr>


<?php

$found = false;


if (
    isset($_SESSION['complaints'])
) {

foreach (
    $_SESSION['complaints']
    as $complaint
) {

if (
    ($complaint['username'] ?? '')
    !== $_SESSION['username']
) {

continue;

}


$found = true;

?>


<tr>

<td>
<?php
echo htmlspecialchars(
    $complaint['id'] ?? ''
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $complaint['name_designation'] ?? ''
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $complaint['contact_number'] ?? ''
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $complaint['department_ward_area'] ?? ''
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $complaint['department_location'] ?? ''
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $complaint['type'] ?? ''
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $complaint['complaint_title'] ?? ''
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $complaint['description'] ?? ''
);
?>
</td>


<td>

<?php

if (
    !empty($complaint['images'])
) {

?>

<div class="photo-grid">

<?php

foreach (
    $complaint['images']
    as $photo
) {

?>

<a
href="<?php
echo htmlspecialchars($photo);
?>"
target="_blank"
>

<img
src="<?php
echo htmlspecialchars($photo);
?>"
alt="Complaint Photo"
>

</a>

<?php

}

?>

</div>

<?php

} else {

echo "No pictures";

}

?>

</td>


<td>

<span class="status">

<?php
echo htmlspecialchars(
    $complaint['status'] ?? ''
);
?>

</span>

</td>


<td>

<?php
echo htmlspecialchars(
    $complaint['assigned_to'] ?? ''
);
?>

<br>

<?php
echo htmlspecialchars(
    $complaint['assigned_contact'] ?? ''
);
?>

</td>


<td>
<?php
echo htmlspecialchars(
    $complaint['work_process'] ?? ''
);
?>
</td>


<td>

<?php
echo htmlspecialchars(
    $complaint['admin_message'] ?? ''
);
?>

<br>

<small>
<?php
echo htmlspecialchars(
    $complaint['admin_message_date'] ?? ''
);
?>
</small>

</td>

</tr>


<?php

}

}


if (!$found) {

?>

<tr>

<td colspan="13">
No complaints registered yet.
</td>

</tr>

<?php

}

?>

</table>

</div>

</div>

<?php } ?>


<!-- =====================================================
     USER REPORTS
===================================================== -->

<?php if ($user_view == "reports") { ?>

<?php

$report_period =
    $_GET['period'] ?? 'monthly';
    if (
    !in_array(
        $report_period,
        [
            'daily',
            'weekly',
            'monthly'
        ]
    )
) {

    $report_period = 'monthly';
}


$total_report =
    getReportCount(
        $report_period,
        null,
        null,
        $_SESSION['username']
    );

$pending_report =
    getReportCount(
        $report_period,
        'Pending',
        null,
        $_SESSION['username']
    );

$working_report =
    getReportCount(
        $report_period,
        'Working',
        null,
        $_SESSION['username']
    );

$delayed_report =
    getReportCount(
        $report_period,
        'Delayed',
        null,
        $_SESSION['username']
    );

$resolved_report =
    getReportCount(
        $report_period,
        'Resolved',
        null,
        $_SESSION['username']
    );

$closed_report =
    getReportCount(
        $report_period,
        'Closed',
        null,
        $_SESSION['username']
    );

?>

<div class="content-box">

<h2>
Complaint Reports
</h2>


<div class="report-period">

<a href="index.php?page=user_dashboard&view=reports&period=daily">
Daily Report
</a>

<a href="index.php?page=user_dashboard&view=reports&period=weekly">
Weekly Report
</a>

<a href="index.php?page=user_dashboard&view=reports&period=monthly">
Monthly Report
</a>

</div>


<h3>
<?php
echo ucfirst(
    $report_period
);
?>
 Report
</h3>


<div class="report-cards">

<div class="report-card">

<h3>
Total Complaints
</h3>

<div class="report-number">
<?php
echo $total_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Pending
</h3>

<div class="report-number">
<?php
echo $pending_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Working
</h3>

<div class="report-number">
<?php
echo $working_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Delayed
</h3>

<div class="report-number">
<?php
echo $delayed_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Resolved
</h3>

<div class="report-number">
<?php
echo $resolved_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Closed
</h3>

<div class="report-number">
<?php
echo $closed_report;
?>
</div>

</div>

</div>


<h3>
360° Status Percentage
</h3>


<div class="circular-report-box">

<?php

statusCircle(
    'Pending',
    $pending_report,
    $total_report,
    'circle-pending'
);

statusCircle(
    'Working',
    $working_report,
    $total_report,
    'circle-working'
);

statusCircle(
    'Delayed',
    $delayed_report,
    $total_report,
    'circle-delayed'
);

statusCircle(
    'Resolved',
    $resolved_report,
    $total_report,
    'circle-resolved'
);

statusCircle(
    'Closed',
    $closed_report,
    $total_report,
    'circle-closed'
);

?>

</div>

</div>

<?php } ?>


<!-- =====================================================
     USER FEEDBACK (rating + message, submits for real)
===================================================== -->

<?php if ($user_view == "feedback") { ?>

<div class="content-box">

<h2>
Feedback
</h2>

<?php if ($feedback_message != "") { ?>

<div class="message<?php echo (strpos(strtolower($feedback_message), 'please') !== false) ? ' error' : ''; ?>">

<?php
echo htmlspecialchars(
    $feedback_message
);
?>

</div>

<?php } ?>

<div class="form-box">

<form method="POST">

<label>
Rate Your Experience
</label>

<div class="rating-group">

<?php for ($star = 5; $star >= 1; $star--) { ?>

<input
type="radio"
id="rating<?php echo $star; ?>"
name="rating"
value="<?php echo $star; ?>"
required
>

<label
for="rating<?php echo $star; ?>"
class="star-label"
>&#9733;</label>

<?php } ?>

</div>


<label>
Your Feedback
</label>

<textarea
name="feedback_text"
placeholder="Share your experience with the complaint system"
required
></textarea>


<button
type="submit"
name="submit_feedback"
class="btn"
>
SUBMIT FEEDBACK
</button>

</form>

</div>


<?php

$all_feedback_data = loadJsonData($feedback_file);

$my_feedback = array_values(
    array_filter(
        $all_feedback_data,
        function ($f) {
            return ($f['username'] ?? '') === $_SESSION['username'];
        }
    )
);

?>

<?php if (count($my_feedback) > 0) { ?>

<h3 style="margin-top:30px;">
Your Previous Feedback
</h3>

<div class="table-container">

<table>

<tr>
<th>Rating</th>
<th>Feedback</th>
<th>Date</th>
</tr>

<?php foreach (array_reverse($my_feedback) as $f) { ?>

<tr>

<td>
<?php
echo str_repeat('&#9733;', intval($f['rating'] ?? 0));
?>
</td>

<td>
<?php
echo htmlspecialchars($f['message'] ?? '');
?>
</td>

<td>
<?php
echo htmlspecialchars($f['date'] ?? '');
?>
</td>

</tr>

<?php } ?>

</table>

</div>

<?php } ?>

</div>

<?php } ?>


</div>

</div>

<?php } ?>


<!-- =====================================================
     ADMIN DASHBOARD
===================================================== -->

<?php if ($page == "admin_dashboard") { ?>

<?php

if (
    !isset($_SESSION['admin_logged_in'])
) {

    header(
        "Location: index.php?page=admin_login"
    );

    exit();
}


$current_admin_type =
    $_SESSION['admin_type'] ?? '';


/*
========================================================
ADMIN DEPARTMENT FILTER
========================================================
*/

$admin_filter =
    $current_admin_type;

?>


<div class="dashboard-layout">


<div class="dashboard-sidebar">

<h3>
<?php
echo htmlspecialchars(
    $current_admin_type
);
?>
 Admin
</h3>


<a
href="index.php?page=admin_dashboard&view=dashboard"
class="<?php
echo $admin_view == 'dashboard'
? 'active'
: '';
?>"
>
Dashboard
</a>


<a
href="index.php?page=admin_dashboard&view=track"
class="<?php
echo $admin_view == 'track'
? 'active'
: '';
?>"
>
Track Complaint
</a>


<a
href="index.php?page=admin_dashboard&view=complaints"
class="<?php
echo $admin_view == 'complaints'
? 'active'
: '';
?>"
>
Complaints
</a>


<a
href="index.php?page=admin_dashboard&view=reports"
class="<?php
echo $admin_view == 'reports'
? 'active'
: '';
?>"
>
Reports
</a>


<a
href="index.php?page=admin_dashboard&view=feedback"
class="<?php
echo $admin_view == 'feedback'
? 'active'
: '';
?>"
>
Feedback
</a>


<a href="index.php?logout=1">
Logout
</a>

</div>


<div class="dashboard-content">


<div class="dashboard-title">

<h2>
<?php
echo htmlspecialchars(
    $current_admin_type
);
?>
 Admin Dashboard
</h2>

<p>
Welcome,
<?php
echo htmlspecialchars(
    $_SESSION['admin_username']
);
?>
</p>

</div>


<!-- =====================================================
     ADMIN DASHBOARD HOME
===================================================== -->

<?php if ($admin_view == "dashboard") { ?>

<div class="content-box">

<h2>
Complaint Overview
</h2>

<p>
New complaints are routed according to their selected
service category. The <?php echo htmlspecialchars($current_admin_type); ?>
administrator can assign work, update status and send
work-process information to the user.
</p>


<?php

$new_notifications = 0;

if (
    isset($_SESSION['notifications'])
) {

foreach (
    $_SESSION['notifications']
    as $notification
) {

if (
    !$notification['read'] &&
    strcasecmp(
        $notification['type'],
        'New Complaint'
    ) === 0
) {

$new_notifications++;

}

}

}

?>


<div class="notification">

<strong>
New Complaint Notifications:
</strong>

<?php
echo $new_notifications;
?>

</div>


<div class="report-cards">

<div class="report-card">

<h3>Total</h3>

<div class="report-number">

<?php
echo getComplaintCount(
    null,
    $admin_filter
);
?>

</div>

</div>


<div class="report-card">

<h3>Pending</h3>

<div class="report-number">

<?php
echo getComplaintCount(
    'Pending',
    $admin_filter
);
?>

</div>

</div>


<div class="report-card">

<h3>Working</h3>

<div class="report-number">

<?php
echo getComplaintCount(
    'Working',
    $admin_filter
);
?>

</div>

</div>


<div class="report-card">

<h3>Delayed</h3>

<div class="report-number">

<?php
echo getComplaintCount(
    'Delayed',
    $admin_filter
);
?>

</div>

</div>


<div class="report-card">

<h3>Resolved</h3>

<div class="report-number">

<?php
echo getComplaintCount(
    'Resolved',
    $admin_filter
);
?>

</div>

</div>


<div class="report-card">

<h3>Closed</h3>

<div class="report-number">

<?php
echo getComplaintCount(
    'Closed',
    $admin_filter
);
?>

</div>

</div>

</div>

</div>

<?php } ?>


<!-- =====================================================
     ADMIN TRACK
===================================================== -->

<?php if ($admin_view == "track") { ?>

<div class="content-box">

<h2>
Track Complaints
</h2>


<div class="table-container">

<table>

<tr>

<th>Complaint ID</th>
<th>User</th>
<th>Service</th>
<th>Location</th>
<th>Title</th>
<th>Status</th>
<th>Assigned To</th>
<th>Date</th>

</tr>


<?php

$found = false;


if (
    isset($_SESSION['complaints'])
) {

foreach (
    $_SESSION['complaints']
    as $complaint
) {


if (
    strcasecmp(
        $complaint['type'] ?? '',
        $admin_filter
    ) !== 0
) {

continue;

}


$found = true;

?>


<tr>

<td>
<?php
echo htmlspecialchars(
    $complaint['id'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['username'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['type'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['department_location']
    ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $complaint['complaint_title']
    ?? ''
);
?>
</td>

<td>

<span class="status">

<?php
echo htmlspecialchars(
    $complaint['status'] ?? ''
);
?>

</span>

</td>

<td>

<?php
echo htmlspecialchars(
    $complaint['assigned_to'] ?? ''
);
?>

<br>

<?php
echo htmlspecialchars(
    $complaint['assigned_contact'] ?? ''
);
?>

</td>

<td>

<?php
echo htmlspecialchars(
    $complaint['date'] ?? ''
);
?>

</td>

</tr>


<?php

}

}


if (!$found) {

?>

<tr>

<td colspan="8">
No complaints registered for this department.
</td>

</tr>

<?php

}

?>

</table>

</div>

</div>

<?php } ?>


<!-- =====================================================
     ADMIN COMPLAINTS
===================================================== -->

<?php if ($admin_view == "complaints") { ?>

<div class="content-box">

<h2>
Registered <?php
echo htmlspecialchars(
    $admin_filter
);
?> Complaints
</h2>


<?php if ($admin_update_message != "") { ?>

<div class="message
<?php
if (
    strpos(
        strtolower(
            $admin_update_message
        ),
        'please'
    ) !== false
) {
    echo ' error';
}
?>
">

<?php
echo htmlspecialchars(
    $admin_update_message
);
?>

</div>

<?php } ?>


<div class="table-container">

<table>

<tr>

<th>Complaint ID</th>
<th>User</th>
<th>Name</th>
<th>Contact</th>
<th>Email</th>
<th>Location</th>
<th>Title</th>
<th>Description</th>
<th>Pictures</th>
<th>Status</th>
<th>Assign / Update</th>

</tr>


<?php

$found = false;


if (
    isset($_SESSION['complaints'])
) {

foreach (
    $_SESSION['complaints']
    as $index => $complaint
) {


if (
    strcasecmp(
        $complaint['type'] ?? '',
        $admin_filter
    ) !== 0
) {

continue;

}


$found = true;

?>


<tr>

<td>

<strong>

<?php
echo htmlspecialchars(
    $complaint['id'] ?? ''
);
?>

</strong>

</td>


<td>

<?php
echo htmlspecialchars(
    $complaint['username'] ?? ''
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $complaint['name_designation']
    ?? ''
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $complaint['contact_number']
    ?? ''
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $complaint['email'] ?? ''
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $complaint['department_location']
    ?? ''
);
?>

<br>

<small>

<?php
echo htmlspecialchars(
    $complaint['department_ward_area']
    ?? ''
);
?>

</small>

</td>


<td>

<?php
echo htmlspecialchars(
    $complaint['complaint_title']
    ?? ''
);
?>

</td>


<td>

<?php
echo htmlspecialchars(
    $complaint['description']
    ?? ''
);
?>

</td>


<td>

<?php

if (
    !empty($complaint['images'])
) {

?>

<div class="photo-grid">

<?php

foreach (
    $complaint['images']
    as $photo
) {

?>

<a
href="<?php
echo htmlspecialchars($photo);
?>"
target="_blank"
>

<img
src="<?php
echo htmlspecialchars($photo);
?>"
alt="Complaint Photo"
>

</a>

<?php

}

?>

</div>

<?php

} else {

echo "No pictures";

}

?>

</td>


<td>

<span class="status">

<?php
echo htmlspecialchars(
    $complaint['status'] ?? ''
);
?>

</span>

<br><br>

<?php

if (
    !empty($complaint['assigned_to'])
) {

?>

<strong>
Assigned:
</strong>

<br>

<?php
echo htmlspecialchars(
    $complaint['assigned_to']
);
?>

<?php

}

?>

</td>


<td>

<div class="assignment-box">

<form method="POST">

<input
type="hidden"
name="complaint_index"
value="<?php
echo $index;
?>"
>


<label>
Assign Work To
</label>

<input
type="text"
name="assigned_to"
placeholder="Worker name"
value="<?php
echo htmlspecialchars(
    $complaint['assigned_to'] ?? ''
);
?>"
required
>


<label>
Worker Contact
</label>

<input
type="tel"
name="assigned_contact"
placeholder="Worker contact number"
value="<?php
echo htmlspecialchars(
    $complaint['assigned_contact'] ?? ''
);
?>"
required
>


<label>
Work Process
</label>

<textarea
name="work_process"
placeholder="Enter current work process"
><?php
echo htmlspecialchars(
    $complaint['work_process'] ?? ''
);
?></textarea>


<label>
Status
</label>

<select
name="status"
class="status-select"
required
>

<option
value="Pending"
<?php
if (
    ($complaint['status'] ?? '')
    === 'Pending'
) {
    echo 'selected';
}
?>
>
Pending
</option>


<option
value="Working"
<?php
if (
    ($complaint['status'] ?? '')
    === 'Working'
) {
    echo 'selected';
}
?>
>
Working
</option>


<option
value="Delayed"
<?php
if (
    ($complaint['status'] ?? '')
    === 'Delayed'
) {
    echo 'selected';
}
?>
>
Delayed
</option>


<option
value="Resolved"
<?php
if (
    ($complaint['status'] ?? '')
    === 'Resolved'
) {
    echo 'selected';
}
?>
>
Resolved
</option>


<option
value="Closed"
<?php
if (
    ($complaint['status'] ?? '')
    === 'Closed'
) {
    echo 'selected';
}
?>
>
Closed
</option>

</select>


<label>
Delay Reason
</label>

<textarea
name="delay_reason"
placeholder="Required only when status is Delayed"
><?php
echo htmlspecialchars(
    $complaint['delay_reason'] ?? ''
);
?></textarea>


<label>
Message to User
</label>

<textarea
name="admin_message"
placeholder="Message sent to the user"
></textarea>


<button
type="submit"
name="update_complaint"
class="btn green-btn"
>
UPDATE & NOTIFY USER
</button>

</form>

</div>

</td>

</tr>


<?php

}

}


if (!$found) {

?>

<tr>

<td colspan="11">
No complaints available for this department.
</td>

</tr>

<?php

}

?>

</table>

</div>

</div>

<?php } ?>


<!-- =====================================================
     ADMIN REPORTS
===================================================== -->

<?php if ($admin_view == "reports") { ?>

<?php

$report_period =
    $_GET['period'] ?? 'monthly';


if (
    !in_array(
        $report_period,
        [
            'daily',
            'weekly',
            'monthly'
        ]
    )
) {

    $report_period = 'monthly';
}


$total_report =
    getReportCount(
        $report_period,
        null,
        $admin_filter
    );

$pending_report =
    getReportCount(
        $report_period,
        'Pending',
        $admin_filter
    );

$working_report =
    getReportCount(
        $report_period,
        'Working',
        $admin_filter
    );

$delayed_report =
    getReportCount(
        $report_period,
        'Delayed',
        $admin_filter
    );

$resolved_report =
    getReportCount(
        $report_period,
        'Resolved',
        $admin_filter
    );

$closed_report =
    getReportCount(
        $report_period,
        'Closed',
        $admin_filter
    );

?>

<div class="content-box">

<h2>
<?php
echo htmlspecialchars(
    $admin_filter
);
?>
 Complaint Reports
</h2>


<div class="report-period">

<a href="index.php?page=admin_dashboard&view=reports&period=daily">
Daily Report
</a>

<a href="index.php?page=admin_dashboard&view=reports&period=weekly">
Weekly Report
</a>

<a href="index.php?page=admin_dashboard&view=reports&period=monthly">
Monthly Report
</a>

</div>


<h3>

<?php
echo ucfirst(
    $report_period
);
?>

 Report

</h3>


<div class="report-cards">

<div class="report-card">

<h3>
Total Complaints
</h3>

<div class="report-number">
<?php
echo $total_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Pending
</h3>

<div class="report-number">
<?php
echo $pending_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Working
</h3>

<div class="report-number">
<?php
echo $working_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Delayed
</h3>

<div class="report-number">
<?php
echo $delayed_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Resolved
</h3>

<div class="report-number">
<?php
echo $resolved_report;
?>
</div>

</div>


<div class="report-card">

<h3>
Closed
</h3>

<div class="report-number">
<?php
echo $closed_report;
?>
</div>

</div>

</div>


<h3>
360° Status Percentage
</h3>


<div class="circular-report-box">

<?php

statusCircle(
    'Pending',
    $pending_report,
    $total_report,
    'circle-pending'
);

statusCircle(
    'Working',
    $working_report,
    $total_report,
    'circle-working'
);

statusCircle(
    'Delayed',
    $delayed_report,
    $total_report,
    'circle-delayed'
);

statusCircle(
    'Resolved',
    $resolved_report,
    $total_report,
    'circle-resolved'
);

statusCircle(
    'Closed',
    $closed_report,
    $total_report,
    'circle-closed'
);

?>

</div>


<div class="content-box"
style="margin-top:25px;">

<h3>
Report Explanation
</h3>

<p>
The circular charts represent the percentage of complaints
for each status during the selected period.
The complete circle represents 360 degrees.
Different colors identify Pending, Working, Delayed,
Resolved and Closed complaints.
</p>

</div>

</div>

<?php } ?>


<!-- =====================================================
     ADMIN FEEDBACK (VIEW ONLY - no submission for admin)
===================================================== -->

<?php if ($admin_view == "feedback") { ?>

<div class="content-box">

<h2>
User Feedback
</h2>

<p>
This section is for viewing feedback and ratings submitted by
hospital staff about the complaint management system. Feedback
can only be submitted by users; the administrator can view it
here but cannot add or edit entries.
</p>

<?php

$all_feedback_admin = loadJsonData($feedback_file);
$feedback_total = count($all_feedback_admin);
$feedback_sum = 0;

foreach ($all_feedback_admin as $fb) {
    $feedback_sum += intval($fb['rating'] ?? 0);
}

$feedback_avg = $feedback_total > 0
    ? round($feedback_sum / $feedback_total, 1)
    : 0;

?>

<div class="report-cards" style="grid-template-columns:repeat(2,1fr);max-width:600px;">

<div class="report-card">

<h3>
Total Feedback Received
</h3>

<div class="report-number">
<?php echo $feedback_total; ?>
</div>

</div>

<div class="report-card">

<h3>
Average Rating
</h3>

<div class="report-number">
<?php echo $feedback_avg; ?> / 5
</div>

</div>

</div>


<div class="table-container" style="margin-top:25px;">

<table>

<tr>
<th>Username</th>
<th>Name</th>
<th>Rating</th>
<th>Feedback</th>
<th>Date</th>
</tr>

<?php if ($feedback_total > 0) { ?>

<?php foreach (array_reverse($all_feedback_admin) as $fb) { ?>

<tr>

<td>
<?php echo htmlspecialchars($fb['username'] ?? ''); ?>
</td>

<td>
<?php echo htmlspecialchars($fb['name'] ?? ''); ?>
</td>

<td>
<?php echo str_repeat('&#9733;', intval($fb['rating'] ?? 0)); ?>
</td>

<td>
<?php echo htmlspecialchars($fb['message'] ?? ''); ?>
</td>

<td>
<?php echo htmlspecialchars($fb['date'] ?? ''); ?>
</td>

</tr>

<?php } ?>

<?php } else { ?>

<tr>
<td colspan="5">
No feedback received yet.
</td>
</tr>

<?php } ?>

</table>

</div>

</div>

<?php } ?>


</div>

</div>

<?php } ?>


</main>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">

<p>
© 2026 AIIMS Raipur
</p>

<p>
Hospital Upkeep Complaint Management System
</p>

</footer>


<!-- =====================================================
     DELAY REASON JAVASCRIPT
===================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const selects =
            document.querySelectorAll(
                ".status-select"
            );


        selects.forEach(
            function(select) {

                const form =
                    select.closest("form");

                if (!form) {
                    return;
                }


                const reason =
                    form.querySelector(
                        '[name="delay_reason"]'
                    );


                function updateReason() {

                    if (!reason) {
                        return;
                    }


                    if (
                        select.value ===
                        "Delayed"
                    ) {

                        reason.required =
                            true;

                        reason.placeholder =
                            "Enter reason for delay";

                        reason.style.background =
                            "#fff4f4";

                    } else {

                        reason.required =
                            false;

                        reason.value =
                            "";

                        reason.placeholder =
                            "Not required unless status is Delayed";

                        reason.style.background =
                            "white";
                    }
                }


                updateReason();


                select.addEventListener(
                    "change",
                    updateReason
                );

            }
        );

    }
);

</script>


</body>

</html>
