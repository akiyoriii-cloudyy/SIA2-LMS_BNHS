<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LMS BNHS Mobile Access</title>
    @php($publicBase = rtrim(parse_url(config('app.url'), PHP_URL_PATH) ?: '', '/'))
    <link rel="stylesheet" href="{{ $publicBase }}/lms.css?v={{ time() }}">
    <script>
        window.__LMS_THEME_SEED = "guest";
    </script>
    <script src="{{ $publicBase }}/lms-theme.js?v={{ time() }}" defer></script>
    <style>
        .download-app-steps {
            margin: 12px 0 0;
            padding: 0;
            list-style: none;
            counter-reset: install-step;
            display: grid;
            gap: 10px;
        }
        .download-app-steps li {
            counter-increment: install-step;
            display: grid;
            grid-template-columns: 2rem 1fr;
            gap: 10px;
            align-items: start;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--cream);
            line-height: 1.5;
            font-size: 14px;
        }
        .download-app-steps li::before {
            content: counter(install-step);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 8px;
            background: var(--navy);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
        }
        .download-app-security-note {
            margin: 14px 0 0;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(232, 184, 75, 0.15);
            border: 1px solid var(--border);
            font-size: 13px;
            line-height: 1.5;
        }
        @media (max-width: 600px) {
            .download-app-steps li {
                grid-template-columns: 1.75rem 1fr;
                padding: 8px 10px;
                font-size: 13px;
            }
        }
        .btn-apk-unavailable {
            width: 100%;
            cursor: pointer;
            opacity: 0.92;
        }
        .btn-apk-unavailable:hover {
            opacity: 1;
        }
    </style>
</head>
<body class="lms lms-guest">
    <main class="main main--guest">
        <div class="container">
            <div class="card" style="max-width:820px;margin:24px auto;">
                <div class="header" style="margin-bottom:10px;">
                    <h1 class="title">LMS BNHS Mobile Access</h1>
                    <p class="subtitle">Bawing National High School</p>
                </div>

                <p class="muted" style="margin-top:0;">
                    Download the mobile app for teacher/adviser attendance monitoring, mobile attendance sync, and RFID/mobile access.
                </p>

                @if (!empty($apkError))
                    <div class="error" role="alert">{{ $apkError }}</div>
                @elseif (session('apk_error'))
                    <div class="error" role="alert">{{ session('apk_error') }}</div>
                @endif

                <div class="dash-kpi-grid dash-kpi-grid--2" style="margin-top:16px;">
                    <section class="dash-kpi kpi-navy">
                        <div class="dash-kpi-top">
                            <div class="dash-kpi-icon">🤖</div>
                            <span class="kpi-trend trend-neutral">Android App</span>
                        </div>
                        <div class="dash-kpi-label">APK Download</div>
                        <div class="dash-kpi-sub" style="margin-bottom:12px;">
                            Latest LMS BNHS Android app build for teacher/adviser/security users.
                        </div>
                        @if ($apkAvailable)
                            <a class="btn btn-primary" href="{{ $publicBase }}/download-app/android" style="width:100%;">Download Android App</a>
                        @else
                            <button type="button"
                                class="btn btn-primary btn-apk-unavailable"
                                id="apk-unavailable-btn"
                                aria-haspopup="dialog"
                                aria-controls="apk-unavailable-modal">
                                Android App Not Available Yet
                            </button>
                            <p class="muted" style="margin-top:10px;">APK file is currently unavailable on this server.</p>
                        @endif
                    </section>

                    <section class="dash-kpi kpi-sage">
                        <div class="dash-kpi-top">
                            <div class="dash-kpi-icon">🌐</div>
                            <span class="kpi-trend trend-neutral">Mobile Web Version</span>
                        </div>
                        <div class="dash-kpi-label">Browser Access</div>
                        <div class="dash-kpi-sub" style="margin-bottom:12px;">
                            Open the web-based mobile attendance page from any browser.
                        </div>
                        <a class="btn btn-outline" href="{{ $publicBase }}/mobile-attendance.html">Open Mobile Attendance Web Version</a>
                    </section>
                </div>

                <section class="card" style="margin-top:16px;">
                    <h3 style="margin-top:0;">iPhone / iOS Users</h3>
                    <p class="muted" style="margin-bottom:0;">
                        For iPhone users, please use the mobile web version through your browser. A native iOS app is not available yet.
                    </p>
                </section>

                <section class="card" style="margin-top:12px;">
                    <h3 style="margin-top:0;">APK Installation Reminder</h3>
                    <p class="muted" style="margin-bottom:0;">
                        After downloading the APK, Android may ask permission to install apps from the browser or file manager.
                        Allow it only if the APK came from the official LMS BNHS website.
                    </p>
                </section>

                <section class="card" style="margin-top:12px;">
                    <h3 style="margin-top:0;">How to Download and Install the Mobile App</h3>
                    <p class="muted" style="margin:0 0 4px;">
                        Follow these steps on your Android phone:
                    </p>
                    <ol class="download-app-steps">
                        <li>Open the LMS BNHS website using your phone browser.</li>
                        <li>Tap <strong>Download Android App</strong>.</li>
                        <li>Wait for the APK file to finish downloading.</li>
                        <li>Open the downloaded APK file.</li>
                        <li>If Android asks for permission to install apps from the browser or file manager, tap <strong>Settings</strong> and allow installation from that source.</li>
                        <li>Go back and tap <strong>Install</strong>.</li>
                        <li>After installation, open the <strong>LMS BNHS Mobile App</strong>.</li>
                        <li>Log in using your teacher/adviser account.</li>
                        <li>Make sure your phone has internet connection before syncing attendance.</li>
                    </ol>
                    <p class="download-app-security-note" role="note">
                        <strong>Security note:</strong> For security, only install the APK downloaded from the official LMS BNHS website. Do not install APK files sent from unknown sources.
                    </p>
                </section>

                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
                    <a class="btn btn-outline" href="{{ $publicBase }}/login">Back to Login</a>
                    <a class="btn btn-outline" href="{{ $publicBase }}/dashboard">Open Dashboard</a>
                </div>
            </div>
        </div>
    </main>

    <div id="apk-unavailable-modal" class="notif-modal" hidden aria-hidden="true">
        <div class="notif-modal__backdrop js-apk-modal-close" tabindex="-1"></div>
        <div class="notif-modal__panel" role="dialog" aria-modal="true" aria-labelledby="apk-unavailable-modal-title">
            <div class="notif-modal__head">
                <h2 id="apk-unavailable-modal-title" class="notif-modal__title">Android App Unavailable</h2>
                <button type="button" class="notif-modal__x js-apk-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="notif-modal__body" style="padding:16px;">
                <p style="margin:0 0 12px;line-height:1.55;">
                    Android app is not available yet. Please contact the administrator or wait until the APK file is uploaded.
                </p>
                <button type="button" class="btn btn-primary js-apk-modal-close" style="width:100%;">OK, Got It</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('apk-unavailable-modal');
            var trigger = document.getElementById('apk-unavailable-btn');
            if (!modal) return;

            function openModal() {
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            if (trigger) {
                trigger.addEventListener('click', openModal);
            }

            modal.querySelectorAll('.js-apk-modal-close').forEach(function (el) {
                el.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });

            @if (request()->boolean('apk_unavailable') || !empty($apkError))
            document.addEventListener('DOMContentLoaded', openModal);
            @endif
        })();
    </script>
</body>
</html>
