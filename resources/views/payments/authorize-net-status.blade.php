<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @php
        $isCancelled = str_contains(strtolower($title), 'cancel');
        $accent = $isCancelled ? '#b45309' : '#15803d';
        $accentSoft = $isCancelled ? 'rgba(245, 158, 11, 0.16)' : 'rgba(34, 197, 94, 0.16)';
        $badge = $isCancelled ? 'Cancelled' : 'Submitted';
        $heroTitle = $isCancelled ? 'Payment Was Not Completed' : 'Payment Submitted Successfully';
        $heroMessage = $isCancelled
            ? 'Your payment was cancelled before it was completed. You can close this page and return whenever you are ready to try again.'
            : 'Thank you. Your payment request was submitted successfully. You may close this page and return to the app or website.';
        $statusLine = $isCancelled ? 'Payment cancelled' : 'Payment submitted';
        $timeline = $isCancelled
            ? [
                [
                    'title' => 'No payment was completed',
                    'text' => 'This screen was closed before the payment process finished.',
                ],
                [
                    'title' => 'You can try again later',
                    'text' => 'Return to the app or website whenever you want to restart your payment.',
                ],
                [
                    'title' => 'It is safe to close this page',
                    'text' => 'Once you are back in the app or website, you can continue from there.',
                ],
            ]
            : [
                [
                    'title' => 'Your payment request was received',
                    'text' => 'Your payment was submitted successfully from this page.',
                ],
                [
                    'title' => 'Your order will reflect the update shortly',
                    'text' => 'If you return to the app or website in a moment, you should be able to review the latest status there.',
                ],
                [
                    'title' => 'You may close this page now',
                    'text' => 'You can safely return to the app or website after finishing here.',
                ],
            ];
    @endphp
    <style>
        :root {
            --page-bg: #eef5fb;
            --ink: #0f172a;
            --muted: #475569;
            --panel: rgba(255, 255, 255, 0.92);
            --line: rgba(15, 23, 42, 0.10);
            --accent: {{ $accent }};
            --accent-soft: {{ $accentSoft }};
            --shadow: 0 30px 60px rgba(15, 23, 42, 0.16);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", "Gill Sans", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(14, 116, 144, 0.14), transparent 30%),
                radial-gradient(circle at bottom right, rgba(37, 99, 235, 0.14), transparent 34%),
                linear-gradient(135deg, #edf6ff 0%, #dbeafe 42%, #eff6ff 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .shell {
            width: min(100%, 980px);
            display: grid;
            gap: 24px;
            align-items: stretch;
        }

        .card {
            position: relative;
            overflow: hidden;
            background: var(--panel);
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 28px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(130deg, rgba(255, 255, 255, 0.58), transparent 35%),
                linear-gradient(0deg, rgba(255, 255, 255, 0.35), transparent 60%);
            pointer-events: none;
        }

        .content {
            position: relative;
            display: grid;
            gap: 28px;
            padding: 28px;
        }

        .brand-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background:
                linear-gradient(145deg, rgba(255,255,255,0.88), rgba(255,255,255,0.38)),
                linear-gradient(145deg, var(--accent-soft), rgba(255,255,255,0.2));
            border: 1px solid rgba(255,255,255,0.72);
            display: grid;
            place-items: center;
            color: var(--accent);
            font: 700 22px/1 Georgia, "Times New Roman", serif;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.55);
        }

        .brand-copy {
            display: grid;
            gap: 2px;
        }

        .brand-copy small {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.16em;
            font-size: 11px;
        }

        .brand-copy strong {
            font: 700 22px/1.1 Georgia, "Times New Roman", serif;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 12px;
        }

        .hero {
            display: grid;
            gap: 14px;
        }

        .hero h1 {
            margin: 0;
            font: 700 clamp(2rem, 4vw, 3.4rem)/0.98 Georgia, "Times New Roman", serif;
            letter-spacing: -0.03em;
            max-width: 12ch;
        }

        .hero p {
            margin: 0;
            max-width: 58ch;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .summary {
            display: grid;
            grid-template-columns: 1.3fr .9fr;
            gap: 20px;
        }

        .panel {
            border-radius: 22px;
            background: rgba(255,255,255,0.72);
            border: 1px solid var(--line);
            padding: 22px;
        }

        .panel h2 {
            margin: 0 0 14px;
            font: 700 1.05rem/1.2 Georgia, "Times New Roman", serif;
        }

        .timeline {
            display: grid;
            gap: 12px;
        }

        .timeline-item {
            display: grid;
            grid-template-columns: 16px 1fr;
            gap: 12px;
            align-items: start;
        }

        .dot {
            width: 12px;
            height: 12px;
            margin-top: 6px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 5px var(--accent-soft);
        }

        .timeline-item strong {
            display: block;
            margin-bottom: 4px;
            font-size: 0.96rem;
        }

        .timeline-item span {
            color: var(--muted);
            line-height: 1.55;
            font-size: 0.95rem;
        }

        .meta-list {
            display: grid;
            gap: 14px;
        }

        .meta-row {
            padding-bottom: 14px;
            border-bottom: 1px solid var(--line);
        }

        .meta-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .meta-row small {
            display: block;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .meta-row strong,
        .meta-row span {
            display: block;
            word-break: break-word;
        }

        .reference {
            display: inline-flex;
            max-width: 100%;
            padding: 10px 12px;
            border-radius: 14px;
            background: #f9fafb;
            border: 1px dashed rgba(31, 41, 55, 0.18);
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding-top: 6px;
            border-top: 1px solid var(--line);
        }

        .footer p {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        @media (max-width: 760px) {
            body {
                padding: 16px;
            }

            .content {
                padding: 20px;
                gap: 22px;
            }

            .brand-row,
            .footer,
            .summary {
                grid-template-columns: 1fr;
                display: grid;
            }

            .badge {
                justify-self: start;
            }

            .hero h1 {
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="card">
            <div class="content">
                <div class="brand-row">
                    <div class="brand">
                        <div class="brand-mark">R</div>
                        <div class="brand-copy">
                            <small>Secure Payment</small>
                            <strong>Reylos Glass</strong>
                        </div>
                    </div>

                    <div class="badge">{{ $badge }}</div>
                </div>

                <div class="hero">
                    <h1>{{ $heroTitle }}</h1>
                    <p>{{ $heroMessage }}</p>
                </div>

                <div class="summary">
                    <section class="panel">
                        <h2>{{ $isCancelled ? 'What This Means' : 'What Happens Next' }}</h2>
                        <div class="timeline">
                            @foreach ($timeline as $item)
                                <div class="timeline-item">
                                    <div class="dot"></div>
                                    <div>
                                        <strong>{{ $item['title'] }}</strong>
                                        <span>{{ $item['text'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <aside class="panel">
                        <h2>Payment Summary</h2>
                        <div class="meta-list">
                            <div class="meta-row">
                                <small>Status</small>
                                <strong>{{ $statusLine }}</strong>
                            </div>
                            @if (!empty($reference))
                                <div class="meta-row">
                                    <small>Confirmation Reference</small>
                                    <span class="reference">{{ $reference }}</span>
                                </div>
                            @endif
                        </div>
                    </aside>
                </div>

                <div class="footer">
                    <p>{{ $isCancelled ? 'You may now return to the app or website whenever you are ready.' : 'You may now return to the app or website to continue.' }}</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
