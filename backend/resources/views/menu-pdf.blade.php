<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 28mm 16mm 20mm;
        }

        body {
            color: #29251f;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.45;
        }

        .header {
            border-bottom: 2px solid #9a783e;
            margin-bottom: 20px;
            padding-bottom: 14px;
            text-align: center;
        }

        .logo {
            height: 54px;
            margin-bottom: 8px;
            width: 54px;
        }

        .brand {
            color: #594524;
            font-size: 21pt;
            font-weight: bold;
            margin: 0;
        }

        .tagline {
            color: #746b5c;
            font-size: 9pt;
            margin: 4px 0 0;
        }

        .generated {
            color: #817663;
            font-size: 8pt;
            margin-top: 9px;
        }

        .category {
            margin: 20px 0 6px;
        }

        .category-title {
            background: #f5efe3;
            border-left: 4px solid #9a783e;
            color: #49391e;
            font-size: 14pt;
            margin: 0;
            padding: 8px 10px;
        }

        .category-description {
            color: #746b5c;
            font-size: 8.5pt;
            margin: 7px 0 4px;
        }

        .item {
            border-bottom: 1px solid #e8e1d5;
            page-break-inside: avoid;
            padding: 9px 2px;
        }

        .item-heading {
            width: 100%;
        }

        .item-name {
            color: #29251f;
            font-size: 10.5pt;
            font-weight: bold;
        }

        .price {
            color: #594524;
            font-size: 10pt;
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
            width: 100px;
        }

        .item-description,
        .options {
            color: #746b5c;
            font-size: 8.5pt;
            margin-top: 3px;
        }

        .status {
            color: #9a4f36;
            font-size: 8pt;
            font-weight: bold;
        }

        .empty {
            color: #817663;
            font-style: italic;
            padding: 8px 2px;
        }
    </style>
</head>
<body>
    <header class="header">
        @if ($logoData)
            <img class="logo" src="{{ $logoData }}" alt="">
        @endif
        <h1 class="brand">Acie Fraiche Cafe</h1>
        <p class="tagline">Freshly Crafted, Simply Delicious</p>
        <div class="generated">Current menu · Generated {{ $generatedAt->format('j M Y, g:i A') }}</div>
    </header>

    @foreach ($categories as $category)
        @php($categoryItems = $itemsByCategory->get($category->id, collect()))
        <section class="category">
            <h2 class="category-title">{{ $category->name }}</h2>
            @if ($category->description)
                <p class="category-description">{{ $category->description }}</p>
            @endif

            @forelse ($categoryItems as $item)
                <article class="item">
                    <table class="item-heading">
                        <tr>
                            <td class="item-name">{{ $item->name }}</td>
                            <td class="price">₦{{ number_format((float) $item->price, 0) }}</td>
                        </tr>
                    </table>
                    @if ($item->description)
                        <div class="item-description">{{ $item->description }}</div>
                    @endif
                    @if ($item->sides)
                        <div class="options">Options: {{ implode(', ', $item->sides) }}</div>
                    @endif
                    @if ($item->is_sold_out)
                        <div class="status">Currently sold out</div>
                    @endif
                </article>
            @empty
                <div class="empty">No active dishes in this category.</div>
            @endforelse
        </section>
    @endforeach

    @if ($uncategorizedItems->isNotEmpty())
        <section class="category">
            <h2 class="category-title">Other items</h2>
            @foreach ($uncategorizedItems as $item)
                <article class="item">
                    <table class="item-heading">
                        <tr>
                            <td class="item-name">{{ $item->name }}</td>
                            <td class="price">₦{{ number_format((float) $item->price, 0) }}</td>
                        </tr>
                    </table>
                    @if ($item->description)
                        <div class="item-description">{{ $item->description }}</div>
                    @endif
                    @if ($item->sides)
                        <div class="options">Options: {{ implode(', ', $item->sides) }}</div>
                    @endif
                    @if ($item->is_sold_out)
                        <div class="status">Currently sold out</div>
                    @endif
                </article>
            @endforeach
        </section>
    @endif
</body>
</html>
