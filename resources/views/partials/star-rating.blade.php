@php
    $ratingValue = max(0, min(5, (int) round((float) ($rating ?? 0))));
@endphp
<span class="star-rating" role="img" aria-label="{{ $ratingValue }} out of 5 stars">
    @for($star = 1; $star <= 5; $star++)
        <i class="bi {{ $star <= $ratingValue ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
    @endfor
</span>
