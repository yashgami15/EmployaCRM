<?php
/** @var array|null $flash */
/** @var string $viewMode */

$isRegister = $viewMode === 'register';
$logoPath = app_logo_path();
$illustrationPath = 'assets/welcome-illustration.svg';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employa HR - <?= $isRegister ? 'Create Account' : 'Sign In' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <main class="auth-shell container-fluid">
        <div class="auth-layout row g-0 min-vh-100">
            <section class="col-lg-7 auth-left d-flex flex-column justify-content-between p-4 p-md-5">
                <div class="d-flex align-items-center gap-2 brand-line">
                    <img src="<?= esc($logoPath) ?>" alt="Employa HR" class="auth-main-logo">
                </div>

                <div class="auth-illustration text-center">
                    <img src="<?= esc($illustrationPath) ?>" alt="Welcome Illustration" class="img-fluid auth-illustration-image">
                    <h1 class="auth-welcome mt-4 mb-2">Welcome Back</h1>
                    <p class="auth-subtitle mb-0">Plan smarter and track faster with an all-in-one hiring workspace.</p>
                </div>

                <p class="small text-muted mb-0">Powerful CRM for candidate, client and interview tracking.</p>
            </section>

            <section class="col-lg-5 auth-right d-flex align-items-center justify-content-center p-3 p-md-5">
                <div class="auth-card card border-0 shadow-lg">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-3">
                            <img src="<?= esc($logoPath) ?>" alt="Employa HR" class="auth-card-logo">
                        </div>
                        <h2 class="auth-title mb-1 text-center"><?= $isRegister ? 'Create Your Account' : "Let's Get Started!" ?></h2>
                        <p class="auth-description mb-4 text-center"><?= $isRegister ? 'Sign up and start managing candidates now.' : 'Continue via email' ?></p>

                        <?php if ($flash): ?>
                            <div class="alert alert-<?= esc($flash['type']) ?>" role="alert">
                                <?= esc($flash['message']) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="index.php?action=<?= $isRegister ? 'register' : 'login' ?>">
                            <?= csrf_field() ?>

                            <?php if ($isRegister): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="name">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" value="<?= esc(old('name')) ?>" required>
                                </div>
                            <?php endif; ?>

                            <?php
                                $defaultCompany = old('company_name');
                                if (empty($defaultCompany) && isset($_COOKIE['remembered_company'])) {
                                    $defaultCompany = $_COOKIE['remembered_company'];
                                }
                            ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="company_name">Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Enter company name" value="<?= esc($defaultCompany) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?= esc(old('email')) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="password">Password <span class="text-danger">*</span></label>
                                <div class="password-wrap position-relative">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                    <button class="btn btn-sm text-secondary password-toggle" type="button" data-password-toggle="password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <?php if ($isRegister): ?>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold" for="confirmPassword">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="password-wrap position-relative">
                                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm password" required>
                                        <button class="btn btn-sm text-secondary password-toggle" type="button" data-password-toggle="confirmPassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold"><?= $isRegister ? 'Create Account' : 'Sign In' ?></button>
                        </form>

                        <p class="text-center mt-4 mb-0 text-secondary">
                            <?php if ($isRegister): ?>
                                Already have an account?
                                <a href="index.php?view=login" class="text-success fw-semibold text-decoration-none">Sign In</a>
                            <?php else: ?>
                                Don't have an account?
                                <a href="index.php?view=register" class="text-success fw-semibold text-decoration-none">Create Account</a>
                            <?php endif; ?>
                        </p>

                        <?php if (!$isRegister): ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
