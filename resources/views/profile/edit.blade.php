@extends('layouts.app')

@section('title', 'Edit Profile - Glyph')

@section('content')
<nav class="navbar">
    <div class="container">
        <div class="navbar-content">
            <a href="{{ route('dashboard') }}" class="navbar-brand">Glyph</a>
            <div class="navbar-nav">
                <a href="{{ route('profile.show', auth()->user()->username) }}" class="btn btn-secondary btn-sm">View Profile</a>
            </div>
        </div>
    </div>
</nav>

<main>
    <div class="container" style="max-width: 800px;">
        <h1 style="margin-bottom: 32px;">Edit Profile</h1>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div style="display: flex; gap: 32px; margin-bottom: 32px;">
                    <div style="text-align: center;">
                        <img src="{{ $user->profile->avatar_url }}" alt="{{ $user->display_name }}" 
                             style="width: 120px; height: 120px; border-radius: 50%; margin-bottom: 16px;">
                        <div class="form-group">
                            <label for="avatar" style="cursor: pointer; color: #667eea;">Change Avatar</label>
                            <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
                        </div>
                    </div>
                    
                    <div style="flex: 1;">
                        <div class="form-group">
                            <label for="display_name">Display Name</label>
                            <input type="text" id="display_name" name="display_name" value="{{ $user->display_name }}" required>
                        </div>

                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" rows="4" placeholder="Tell us about yourself...">{{ $user->profile->bio }}</textarea>
                            <small style="color: #71717a; font-size: 12px;">Max 500 characters</small>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('profile.show', $user->username) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('img[alt="{{ $user->display_name }}"]').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endsection