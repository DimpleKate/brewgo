@extends('layouts.admin')

@section('title', 'Edit '.$addon->name)

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Edit add-on</h1>
        <p class="admin-page-subtitle text-muted-bp">{{ $addon->name }}</p>
    </div>
</div>

<div class="admin-card admin-form-card">
    <form action="{{ route('admin.addons.update', $addon) }}" method="POST" data-submit-once>
        @csrf
        @method('PUT')
        @include('admin.addons._form', ['addon' => $addon])
    </form>
</div>
@endsection
