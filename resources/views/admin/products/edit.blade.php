@extends('layouts.admin')

@section('title', 'Edit '.$product->name)

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Edit product</h1>
        <p class="admin-page-subtitle text-muted-bp">{{ $product->name }} · ₱{{ number_format((float) $product->base_price, 2) }}</p>
    </div>
</div>

<div class="admin-card admin-form-card">
    <div class="footer-title">Product details</div>
    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" data-submit-once>
        @csrf
        @method('PUT')
        @include('admin.products._form', ['product' => $product])
    </form>
</div>
@endsection
