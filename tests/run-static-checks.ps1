Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$TestsDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $TestsDir
$Failed = 0
$Passed = 0

function Pass($Message) {
    $script:Passed += 1
    Write-Host "[PASS] $Message"
}

function Fail($Message) {
    $script:Failed += 1
    Write-Host "[FAIL] $Message"
}

function RelPath($Path) {
    return Join-Path $ProjectRoot $Path
}

function Test-RequiredPath($Path, $Kind) {
    $FullPath = RelPath $Path
    if (-not (Test-Path -LiteralPath $FullPath)) {
        Fail "Missing required path: $Path"
        return
    }

    $Item = Get-Item -LiteralPath $FullPath
    if ($Kind -eq "Directory" -and -not $Item.PSIsContainer) {
        Fail "Expected directory but found file: $Path"
        return
    }

    if ($Kind -eq "File" -and $Item.PSIsContainer) {
        Fail "Expected file but found directory: $Path"
        return
    }

    Pass "Required $($Kind.ToLower()) exists: $Path"
}

function Read-Text($Path) {
    return Get-Content -LiteralPath (RelPath $Path) -Raw
}

function Test-FileContainsAny($Path, $Patterns, $Description) {
    $FullPath = RelPath $Path
    if (-not (Test-Path -LiteralPath $FullPath)) {
        Fail "$Description cannot be checked because file is missing: $Path"
        return
    }

    $Content = Read-Text $Path
    foreach ($Pattern in $Patterns) {
        if ($Content -match $Pattern) {
            Pass $Description
            return
        }
    }

    Fail "$Description. Expected one of: $($Patterns -join ', ') in $Path"
}

function Test-PhpFile($Path) {
    $Content = Get-Content -LiteralPath $Path -Raw
    $Relative = $Path.Substring($ProjectRoot.Length).TrimStart("\", "/")

    if ($Content -match "<\?php") {
        Pass "PHP opening tag found: $Relative"
    } else {
        Fail "Missing standard <?php opening tag: $Relative"
    }

    if ($Content -match "<\?(?!php|=)") {
        Fail "Short PHP tag found; use <?php instead: $Relative"
    } else {
        Pass "No short PHP tag found: $Relative"
    }

    $UnsafeSqlPattern = '(?is)(SELECT|INSERT|UPDATE|DELETE)[^;`"]*(\$_GET|\$_POST|\$_REQUEST)'
    if ($Content -match $UnsafeSqlPattern) {
        Fail "Possible raw request data interpolation in SQL: $Relative"
    } else {
        Pass "No obvious raw request data interpolation in SQL: $Relative"
    }
}

$RequiredDirectories = @(
    "assets",
    "assets\css",
    "assets\js",
    "assets\uploads",
    "assets\uploads\job-order-images",
    "config",
    "includes",
    "user",
    "admin",
    "auth"
)

$RequiredFiles = @(
    "assets\css\style.css",
    "assets\js\main.js",
    "config\database.php",
    "includes\header.php",
    "includes\sidebar.php",
    "includes\footer.php",
    "includes\auth_check.php",
    "user\dashboard.php",
    "user\manage_job_orders.php",
    "user\create_job_order.php",
    "user\view_job_order.php",
    "user\export_pdf.php",
    "user\import_job_order.php",
    "admin\dashboard.php",
    "admin\edit_job_order.php",
    "admin\view_job_order.php",
    "admin\export_pdf.php",
    "admin\import_job_order.php",
    "admin\version_history.php",
    "auth\login.php",
    "auth\logout.php",
    "auth\register.php",
    "index.php"
)

Write-Host "Static verification target: $ProjectRoot"
Write-Host ""

foreach ($Directory in $RequiredDirectories) {
    Test-RequiredPath $Directory "Directory"
}

foreach ($File in $RequiredFiles) {
    Test-RequiredPath $File "File"
}

Write-Host ""

$PhpFiles = @()
if (Test-Path -LiteralPath $ProjectRoot) {
    $PhpFiles = @(Get-ChildItem -LiteralPath $ProjectRoot -Recurse -File -Filter "*.php" |
        Where-Object { $_.FullName -notlike (Join-Path $TestsDir "*") })
}

if ($PhpFiles.Count -eq 0) {
    Fail "No PHP files found under job-order-system. Create project files before feature validation can pass."
} else {
    foreach ($PhpFile in $PhpFiles) {
        Test-PhpFile $PhpFile.FullName
    }
}

Write-Host ""

Test-FileContainsAny "config\database.php" @('new\s+PDO', 'mysqli_connect', 'new\s+mysqli') "Database config defines a PDO or MySQLi connection"
Test-FileContainsAny "auth\login.php" @('session_start\s*\(', 'password_verify\s*\(') "Login uses sessions or password verification"
Test-FileContainsAny "auth\register.php" @('password_hash\s*\(') "Registration hashes passwords"
Test-FileContainsAny "includes\auth_check.php" @('session_start\s*\(', '\$_SESSION') "Auth guard checks PHP session state"

$RoleGuardedPages = @(
    "user\dashboard.php",
    "user\manage_job_orders.php",
    "user\create_job_order.php",
    "admin\dashboard.php",
    "admin\edit_job_order.php",
    "admin\version_history.php"
)

foreach ($Page in $RoleGuardedPages) {
    Test-FileContainsAny $Page @('auth_check\.php', 'require(_once)?\s+.*auth', 'include(_once)?\s+.*auth', '\$_SESSION') "Role-gated page includes an auth/session guard: $Page"
}

Test-FileContainsAny "user\create_job_order.php" @('requesting_department', 'project_name', 'date_needed', 'job_description', 'urgency') "Create job order page contains core job-order fields"
Test-FileContainsAny "user\manage_job_orders.php" @('search', 'sort', 'filter', 'export_pdf', 'import_job_order') "User manage page contains search/sort/filter/import/export signals"
Test-FileContainsAny "admin\dashboard.php" @('Pending', 'Approved', 'Archived', 'Total', 'status') "Admin dashboard contains status summary signals"
Test-FileContainsAny "admin\edit_job_order.php" @('job_order_history', 'old_value', 'new_value', 'admin_comment') "Admin edit page contains comment/history tracking signals"
Test-FileContainsAny "admin\version_history.php" @('field_changed', 'old_value', 'new_value', 'edited_at') "Version history page contains audit field signals"
Test-FileContainsAny "user\export_pdf.php" @('TCPDF', 'FPDF', 'Output\s*\(', 'job order form') "User PDF export contains PDF generation signals"
Test-FileContainsAny "admin\export_pdf.php" @('TCPDF', 'FPDF', 'Output\s*\(', 'job order form') "Admin PDF export contains PDF generation signals"
Test-FileContainsAny "user\import_job_order.php" @('ocr', 'tesseract', 'textract', 'vision', 'Pending Review', 'preview') "User import page contains OCR review workflow signals"
Test-FileContainsAny "admin\import_job_order.php" @('ocr', 'tesseract', 'textract', 'vision', 'Pending Review', 'preview') "Admin import page contains OCR review workflow signals"

Write-Host ""
Write-Host "Static checks complete: $Passed passed, $Failed failed."

if ($Failed -gt 0) {
    exit 1
}

exit 0
