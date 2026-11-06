@extends('layouts.admin')

@section('title', 'Administrators')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Administrators</h1>
        <p class="admin-page-subtitle text-muted-bp">Manage staff access and roles. Only super administrators can use this page.</p>
    </div>
    <span class="badge text-bg-secondary align-self-start">{{ $staff->count() }} {{ \Illuminate\Support\Str::plural('account', $staff->count()) }}</span>
</div>

<div class="admin-card mb-3">
    <div class="footer-title">Create administrator</div>
    <form action="{{ route('admin.staff.store') }}" method="POST" data-submit-once>
        @csrf
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="newName">Name</label>
                <input id="newName" name="name" type="text" maxlength="255" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="newEmail">Email</label>
                <input id="newEmail" name="email" type="email" maxlength="255" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="newPhone">Phone</label>
                <input id="newPhone" name="phone" type="tel" maxlength="30" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="newRole">Role</label>
                <select id="newRole" name="role" class="form-select form-select-sm" required>
                    @foreach($roles as $value => $label)
                        <option value="{{ $value }}" @selected($value === 'staff')>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="newPassword">Password</label>
                <input id="newPassword" name="password" type="password" autocomplete="new-password" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="newPasswordConfirmation">Confirm</label>
                <input id="newPasswordConfirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-gold btn-sm w-100" type="submit">Create</button>
            </div>
        </div>
        @if($errors->any())
            <div class="alert alert-danger py-2 px-3 small mt-3 mb-0">{{ $errors->first() }}</div>
        @endif
    </form>
</div>

<div class="admin-card">
    @if($staff->isEmpty())
        <div class="empty-state"><i class="bi bi-people"></i><p class="mb-0">No administrator accounts.</p></div>
    @else
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        <th>Administrator</th>
                        <th>Role</th>
                        <th>2FA</th>
                        <th>Status</th>
                        <th class="text-end">Save</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staff as $member)
                        <tr>
                            <td>
                                <form id="staff-{{ $member->id }}" action="{{ route('admin.staff.update', $member) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                </form>
                                <div class="d-flex align-items-center gap-2">
                                    @include('partials.avatar', ['user' => $member, 'size' => 'sm'])
                                    <div class="flex-grow-1">
                                        <input form="staff-{{ $member->id }}" name="name" type="text" value="{{ $member->name }}" maxlength="255" class="form-control form-control-sm mb-1" required>
                                        <input form="staff-{{ $member->id }}" name="email" type="email" value="{{ $member->email }}" maxlength="255" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select form="staff-{{ $member->id }}" name="role" class="form-select form-select-sm" @disabled($member->is(auth()->user()))>
                                    @foreach($roles as $value => $label)
                                        <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <span class="badge {{ $member->hasTwoFactorEnabled() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $member->hasTwoFactorEnabled() ? 'Enabled' : 'Off' }}</span>
                            </td>
                            <td>
                                <label class="form-check form-switch mb-0">
                                    <input form="staff-{{ $member->id }}" class="form-check-input" type="checkbox" name="is_active" value="1" @checked($member->is_active) @disabled($member->is(auth()->user()))>
                                    <span class="form-check-label small">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
                                </label>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button form="staff-{{ $member->id }}" class="btn btn-soft btn-sm" type="submit">Save</button>
                                    @if(! $member->is(auth()->user()))
                                        <form action="{{ route('admin.staff.destroy', $member) }}" method="POST" data-confirm="Deactivate {{ $member->name }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Deactivate</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
