<x-layout>
    <div style="margin-top: 50px;">
        <div style="max-width: 900px; margin: 0 auto; padding: 32px 16px 48px;">
            <div style="background: #ffffff; border: 1px solid #d7ddd8; border-radius: 20px; box-shadow: 0 20px 40px rgba(38, 49, 40, 0.08); overflow: hidden;">
                <div style="    background: linear-gradient(160deg, #2a0d41 0%, #6912b1 100%); color: #ffffff; padding: 28px 30px;">
                    <div style="font-size: 0.8rem; letter-spacing: 0.12em; text-transform: uppercase; opacity: 0.8; margin-bottom: 8px;">Profile settings</div>
                    <h1 style="margin: 0; font-size: clamp(2rem, 3vw, 2.6rem);">Edit your profile</h1>
                </div>

                <div style="padding: 30px;">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @if ($errors->any())
                            <div style="background: #fff1f2; border: 1px solid #f3b6c0; border-radius: 12px; padding: 14px 16px; margin-bottom: 24px; color: #7f1d1d;">
                                <ul style="margin: 0; padding-left: 18px;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div style="display: grid; grid-template-columns: 260px 1fr; gap: 28px; align-items: start;">
                            <div style="background: #f5f7f4; border: 1px solid #d7ddd8; border-radius: 18px; padding: 22px; text-align: center;">
                                <div style="font-size: 0.8rem; letter-spacing: 0.08em; text-transform: uppercase; color: #6b736e; margin-bottom: 18px;">Current photo</div>

                                @if (auth()->user()->photo)
                                    <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Profile Picture" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #c9d2cc; margin-bottom: 14px;">
                                @else
                                    <img src="{{ asset('images/Default_pfp.jpg') }}" alt="Profile Picture" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #c9d2cc; margin-bottom: 14px;">
                                @endif

                                <div style="font-size: 1.1rem; font-weight: 700; color: #233026; margin-bottom: 4px;">{{ auth()->user()->username }}</div>
                                <div style="color: #4a4f4b; font-size: 0.95rem;">{{ auth()->user()->email }}</div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 22px;">
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="username" style="font-weight: 600; color: #233026;">Username</label>
                                    <input type="text" id="username" name="username" value="{{ auth()->user()->username }}" required
                                        style="width: 100%; padding: 14px 16px; border: 1px solid #cdd7d0; border-radius: 12px; background: #f8faf8; font-size: 1rem; color: #233026; box-sizing: border-box;">
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="email" style="font-weight: 600; color: #233026;">Email</label>
                                    <input type="email" id="email" name="email" value="{{ auth()->user()->email }}" required
                                        style="width: 100%; padding: 14px 16px; border: 1px solid #cdd7d0; border-radius: 12px; background: #f8faf8; font-size: 1rem; color: #233026; box-sizing: border-box;">
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="profile_picture" style="font-weight: 600; color: #233026;">Profile picture</label>
                                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*"
                                        style="width: 100%; padding: 12px 14px; border: 1px dashed #b7c6bd; border-radius: 12px; background: #f8faf8; color: #4a4f4b; box-sizing: border-box;">
                                    <small style="color: #6b736e;">Upload a new profile photo. JPG, PNG and JPEG formats are supported.</small>
                                </div>

                                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px; flex-wrap: wrap;">
                                    <a href="{{ route('profile.view') }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 13px 18px; border-radius: 12px; border: 1px solid #cdd7d0; background: #ffffff; color: #233026; text-decoration: none; font-weight: 600;">
                                        Cancel
                                    </a>
                                    <button type="submit" style="padding: 13px 22px; border: none; border-radius: 12px; background: linear-gradient(135deg, #1f3a2d 0%, #2d5f4b 100%); color: #ffffff; font-weight: 700; cursor: pointer; box-shadow: 0 12px 20px rgba(31, 58, 45, 0.2);">
                                        Save changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>