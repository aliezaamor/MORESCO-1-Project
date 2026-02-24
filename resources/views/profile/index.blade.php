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
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

                </div>
            </div>

            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; pt: 1rem; border-top: 1px solid #f1f5f9; padding-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 2rem; font-weight: 600; font-size: 0.875rem;">
                    Update Profile
                </button>
            </div>
        </form>
    </div>
        </form>
    </div>
</div>
@endsection
