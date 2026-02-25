@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div style="max-width: 1000px; margin: 0 auto;">
    @if (session('success'))
        <div style="background: #ecfdf5; color: #047857; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #a7f3d0; font-size: 0.875rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card" style="padding: 1.5rem;">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div style="display:flex; gap:1.5rem; align-items:center;">
                <div style="width:120px; height:120px; border-radius:8px; overflow:hidden; background:#f3f4f6; display:flex; align-items:center; justify-content:center;">
                    @if ($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="avatar" style="width:100%; height:100%; object-fit:cover;" />
                    @else
                        <span style="color:#94a3b8;">No photo</span>
                    @endif
                </div>

                <div style="flex:1;">
                    <label style="display:block; font-weight:600; margin-bottom:0.25rem;">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" style="width:100%; padding:0.5rem; border:1px solid #e6e6e6; border-radius:6px;" />
                    @error('name') <div style="color:#dc2626; margin-top:0.25rem;">{{ $message }}</div> @enderror

                    <label style="display:block; font-weight:600; margin-top:0.75rem; margin-bottom:0.25rem;">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" style="width:100%; padding:0.5rem; border:1px solid #e6e6e6; border-radius:6px;" />
                    @error('email') <div style="color:#dc2626; margin-top:0.25rem;">{{ $message }}</div> @enderror

                    <label style="display:block; font-weight:600; margin-top:0.75rem; margin-bottom:0.25rem;">Profile Photo</label>
                    <input type="file" name="avatar" accept="image/*" />
                    @error('avatar') <div style="color:#dc2626; margin-top:0.25rem;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; pt: 1rem; border-top: 1px solid #f1f5f9; padding-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 2rem; font-weight: 600; font-size: 0.875rem;">
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
