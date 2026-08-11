@php
    $pageGuide = \App\Support\PageHelp::forRoute(request()->route()?->getName());
    $pageHelpVariant = $variant ?? 'app';
@endphp

<details class="page-help page-help--{{ $pageHelpVariant }}" data-page-help>
    <summary>
        <span class="page-help__summary-main">
            <span class="page-help__icon" aria-hidden="true">?</span>
            <span>
                <strong>এই পেজের বিস্তারিত নির্দেশিকা</strong>
                <small>প্রয়োজন হলে ক্লিক করে খুলুন</small>
            </span>
        </span>
        <span class="page-help__toggle" aria-hidden="true"></span>
    </summary>

    <div class="page-help__content">
        <header class="page-help__header">
            <span class="page-help__eyebrow">PAGE GUIDE</span>
            <h2>{{ $pageGuide['title'] }}</h2>
            <p>{{ $pageGuide['intro'] }}</p>
        </header>

        <div class="page-help__grid">
            <section>
                <h3>এই পেজে যা করতে পারবেন</h3>
                <ul>
                    @foreach ($pageGuide['features'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>
            <section>
                <h3>ব্যবহারের ধাপ</h3>
                <ol>
                    @foreach ($pageGuide['steps'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            </section>
            <section>
                <h3>গুরুত্বপূর্ণ বিষয়</h3>
                <ul>
                    @foreach ($pageGuide['notes'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</details>
