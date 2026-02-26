@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<style>
    /* Ensure placeholder and modal title are visible in dark mode */
    body.dark-mode #avatarUpload span,
    body.dark-mode #avatarModal h3 {
        color: black !important;
    }
    /* Button text color: black in light mode, white in dark mode */
    #avatarModal button {
        color: #333333 !important;
    }
    body.dark-mode #avatarModal button {
        color: white !important;
    }

    /* Lightbox Styles */
    #lightbox {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10001;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    #lightbox.show {
        display: flex;
        opacity: 1;
    }
    #lightbox img {
        max-width: 90%;
        max-height: 85%;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0,0,0,0.5);
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }
    #lightbox.show img {
        transform: scale(1);
    }
    #lightbox-close {
        position: absolute;
        top: 20px;
        right: 20px;
        color: white;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 50%;
        width: 44px;
        height: 44px;
        font-size: 2rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        z-index: 10002;
    }
    #lightbox-close:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }
</style>
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

            <div style="display:flex; gap:6.5rem; align-items:flex-start;">
                <div style="display:flex; flex-direction:column; gap:0.5rem; width:120px;">
                    <div id="avatarUpload" style="width:210px; height:210px; border-radius:8px; overflow:hidden; background:#f3f4f6; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s ease; position:relative;">
                        @if ($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="avatar" style="width:100%; height:100%; object-fit:cover;" />
                        @else
                            <span style="color: #64748b; font-weight: 600; font-size: 0.9rem;">Click To Add Photo</span>
                        @endif
                        <div style="position:absolute; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0); border-radius:8px; transition:background 0.2s ease;" class="avatar-hover-overlay"></div>
                    </div>
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none;" />
                    <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">
                    @error('avatar') <div style="color:#dc2626; margin-top:0.25rem; font-size:0.85rem;">{{ $message }}</div> @enderror
                </div>

                <div style="flex:1;">
                    <label style="display:block; font-weight:600; margin-bottom:0.25rem;">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" style="width:100%; padding:0.5rem; border:1px solid #e6e6e6; border-radius:6px;" />
                    @error('name') <div style="color:#dc2626; margin-top:0.25rem;">{{ $message }}</div> @enderror

                    <label style="display:block; font-weight:600; margin-top:0.75rem; margin-bottom:0.25rem;">Username</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" style="width:100%; padding:0.5rem; border:1px solid #e6e6e6; border-radius:6px;" />
                    @error('username') <div style="color:#dc2626; margin-top:0.25rem;">{{ $message }}</div> @enderror

                    <label style="display:block; font-weight:600; margin-top:0.75rem; margin-bottom:0.25rem;">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" style="width:100%; padding:0.5rem; border:1px solid #e6e6e6; border-radius:6px;" />
                    @error('email') <div style="color:#dc2626; margin-top:0.25rem;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.25rem;">Role</label>
                <select name="role" style="width:100%; padding:0.5rem; border:1px solid #e6e6e6; border-radius:6px;">
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="manager" {{ old('role', $user->role) === 'manager' ? 'selected' : '' }}>Manager</option>
                    <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>User</option>
                </select>
                @error('role') <div style="color:#dc2626; margin-top:0.25rem;">{{ $message }}</div> @enderror

                <label style="display:block; font-weight:600; margin-top:0.75rem; margin-bottom:0.25rem;">Position</label>
                <input type="text" name="position" value="{{ old('position', $user->position) }}" placeholder="e.g. Senior Manager" style="width:100%; padding:0.5rem; border:1px solid #e6e6e6; border-radius:6px;" />
                @error('position') <div style="color:#dc2626; margin-top:0.25rem;">{{ $message }}</div> @enderror

                <label style="display:block; font-weight:600; margin-top:0.75rem; margin-bottom:0.25rem;">Address</label>
                <textarea name="address" rows="3" placeholder="Enter your full address" style="width:100%; padding:0.5rem; border:1px solid #e6e6e6; border-radius:6px; font-family:inherit;">{{ old('address', $user->address) }}</textarea>
                @error('address') <div style="color:#dc2626; margin-top:0.25rem;">{{ $message }}</div> @enderror

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

<script>
    const avatarUpload = document.getElementById('avatarUpload');
    const avatarInput = document.getElementById('avatarInput');
    const hoverOverlay = document.querySelector('.avatar-hover-overlay');

    // Show modal when avatar is clicked
    avatarUpload.addEventListener('click', () => {
        showAvatarMenu();
    });

    // Add hover effect to show avatar is clickable
    avatarUpload.addEventListener('mouseover', () => {
        hoverOverlay.style.background = 'rgba(0,0,0,0.4)';
    });

    avatarUpload.addEventListener('mouseout', () => {
        hoverOverlay.style.background = 'rgba(0,0,0,0)';
    });

    const removeAvatarInput = document.getElementById('removeAvatarInput');

    // Function to show the avatar menu modal
    function showAvatarMenu() {
        // Remove existing modal if any
        const existingModal = document.getElementById('avatarModal');
        if (existingModal) existingModal.remove();

        const hasAvatar = !!avatarUpload.querySelector('img');

        // Create modal
        const modal = document.createElement('div');
        modal.id = 'avatarModal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;

        const content = document.createElement('div');
        content.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            width: 90%;
            max-width: 320px;
        `;

        const title = document.createElement('h3');
        title.textContent = 'Avatar Options';
        title.style.cssText = 'margin: 0 0 1.25rem 0; font-size: 1.25rem; font-weight: 700; color: #1e293b; display: block;';

        const viewBtn = document.createElement('button');
        viewBtn.textContent = 'View Avatar';
        viewBtn.type = 'button';
        viewBtn.style.cssText = `
            display: ${hasAvatar ? 'block' : 'none'};
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            background: #5f656cff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        `;
        viewBtn.addEventListener('mouseover', () => viewBtn.style.background = '#4b5563'); // Darker for hover
        viewBtn.addEventListener('mouseout', () => viewBtn.style.background = '#5f656cff'); // Return to your new color
        viewBtn.addEventListener('click', () => {
            const img = avatarUpload.querySelector('img');
            if (img) {
                showLightbox(img.src);
            }
            modal.remove();
        });

        const changeBtn = document.createElement('button');
        changeBtn.textContent = 'Choose Avatar';
        changeBtn.type = 'button';
        changeBtn.style.cssText = `
            display: block;
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            background: #0ea5e9;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        `;
        changeBtn.addEventListener('mouseover', () => changeBtn.style.background = '#0284c7');
        changeBtn.addEventListener('mouseout', () => changeBtn.style.background = '#0ea5e9');
        changeBtn.addEventListener('click', () => {
            avatarInput.click();
            modal.remove();
        });

        const removeBtn = document.createElement('button');
        removeBtn.textContent = 'Remove Avatar';
        removeBtn.type = 'button';
        removeBtn.style.cssText = `
            display: ${hasAvatar ? 'block' : 'none'};
            width: 100%;
            padding: 0.75rem;
            background: #ef4444;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        `;
        removeBtn.addEventListener('mouseover', () => removeBtn.style.background = '#dc2626');
        removeBtn.addEventListener('mouseout', () => removeBtn.style.background = '#ef4444');
        removeBtn.addEventListener('click', () => {
            // Set remove input to 1
            removeAvatarInput.value = '1';
            // Clear file input
            avatarInput.value = '';
            // Update preview to default placeholder
            const img = avatarUpload.querySelector('img');
            if (img) img.remove();
            
            if (!avatarUpload.querySelector('span')) {
                const span = document.createElement('span');
                span.textContent = 'Click To Add Photo';
                span.style.cssText = 'color: #64748b; font-weight: 600; font-size: 0.9rem;';
                avatarUpload.appendChild(span);
            }
            
            modal.remove();
        });

        content.appendChild(title);
        content.appendChild(viewBtn);
        content.appendChild(changeBtn);
        content.appendChild(removeBtn);
        modal.appendChild(content);

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        document.body.appendChild(modal);
    }

    // Update avatar preview when file is selected
    avatarInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            // Reset remove flag
            removeAvatarInput.value = '0';
            
            const reader = new FileReader();
            reader.onload = (event) => {
                const img = avatarUpload.querySelector('img');
                if (img) {
                    img.src = event.target.result;
                    // Hide "No photo" text if existed
                    const span = avatarUpload.querySelector('span');
                    if (span) span.remove();
                } else {
                    // Create new img element
                    const imgEl = document.createElement('img');
                    imgEl.src = event.target.result;
                    imgEl.style.cssText = 'width:100%; height:100%; object-fit:cover;';
                    const span = avatarUpload.querySelector('span');
                    if (span) span.remove();
                    avatarUpload.appendChild(imgEl);
                }
            };
            reader.readAsDataURL(file);
        }
    });

    // Lightbox Logic
    const lightbox = document.createElement('div');
    lightbox.id = 'lightbox';
    const closeBtn = document.createElement('button');
    closeBtn.id = 'lightbox-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.title = 'Back to Profile';
    const lightboxImg = document.createElement('img');
    lightboxImg.id = 'lightbox-img';
    
    lightbox.appendChild(closeBtn);
    lightbox.appendChild(lightboxImg);
    document.body.appendChild(lightbox);

    function showLightbox(src) {
        lightboxImg.src = src;
        lightbox.style.display = 'flex';
        setTimeout(() => lightbox.classList.add('show'), 10);
    }

    function hideLightbox() {
        lightbox.classList.remove('show');
        setTimeout(() => {
            lightbox.style.display = 'none';
            lightboxImg.src = '';
        }, 300);
    }

    closeBtn.addEventListener('click', hideLightbox);
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) hideLightbox();
    });
</script>
@endsection
