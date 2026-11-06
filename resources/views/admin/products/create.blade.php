@extends('layouts.admin')

@section('title', 'New Product')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">New product</h1>
        <p class="admin-page-subtitle text-muted-bp">Set the item details, drink sizes, and add-ons customers will see.</p>
    </div>
</div>

<div class="admin-card admin-form-card">
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" data-submit-once>
        @csrf
        @include('admin.products._form')
    </form>
</div>
@endsection
