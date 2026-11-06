@extends('layouts.admin')

@section('title', 'New Promotion')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">New promotion</h1>
        <p class="admin-page-subtitle text-muted-bp">Create a discount and optional promo code.</p>
    </div>
</div>

<div class="admin-card admin-form-card">
    <form action="{{ route('admin.promotions.store') }}" method="POST" enctype="multipart/form-data" data-submit-once>
        @csrf
        @include('admin.promotions._form')
    </form>
</div>
@endsection
