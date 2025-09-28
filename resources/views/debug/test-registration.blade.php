<!DOCTYPE html>
<html>
<head>
    <title>Registration Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .error { color: red; margin-top: 5px; }
        .debug-info { background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 4px; }
        pre { background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registration Debug Test</h1>

        <div class="debug-info">
            <h3>Debug Information</h3>
            <p><strong>Environment:</strong> {{ app()->environment() }}</p>
            <p><strong>Debug Mode:</strong> {{ config('app.debug') ? 'Enabled' : 'Disabled' }}</p>
            <p><strong>Database:</strong> {{ DB::connection()->getPdo() ? 'Connected' : 'Failed' }}</p>
            <p><strong>Current User:</strong> {{ Auth::user() ? Auth::user()->email : 'Not logged in' }}</p>
        </div>

        @if(session('success'))
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('registration_success'))
            <div style="background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #bee5eb;">
                <h4>Registration Success Debug Info:</h4>
                <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; overflow-x: auto;">{{ json_encode(session('registration_success'), JSON_PRETTY_PRINT) }}</pre>
                <p><strong>Next Step:</strong> <a href="{{ route('register.documents') }}" target="_blank">Go to Document Upload</a></p>
            </div>
        @endif

        @if($errors->any())
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0;">
                <h4>Validation Errors:</h4>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('debug_error'))
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #ffeaa7;">
                <h4>Debug Error Details:</h4>
                <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; overflow-x: auto;">{{ json_encode(session('debug_error'), JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <h3>Personal Information</h3>
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name', $testData['first_name']) }}" required>
            </div>

            <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <input type="text" name="middle_name" value="{{ old('middle_name', $testData['middle_name']) }}" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name', $testData['last_name']) }}" required>
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $testData['date_of_birth']) }}" required>
            </div>

            <div class="form-group">
                <label for="gender">Gender</label>
                <select name="gender" required>
                    <option value="Male" {{ old('gender', $testData['gender']) == 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('gender', $testData['gender']) == 'Female' ? 'selected' : '' }}>Female</option>
                    <option value="Other" {{ old('gender', $testData['gender']) == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <h3>Account Information</h3>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" value="{{ old('email', $testData['email']) }}" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" value="{{ $testData['password'] }}" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" name="password_confirmation" value="{{ $testData['password_confirmation'] }}" required>
            </div>

            <h3>Restaurant Information</h3>
            <div class="form-group">
                <label for="restaurant_name">Restaurant Name</label>
                <input type="text" name="restaurant_name" value="{{ old('restaurant_name', $testData['restaurant_name']) }}" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea name="address" required>{{ old('address', $testData['address']) }}</textarea>
            </div>

            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $testData['postal_code']) }}">
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number', $testData['contact_number']) }}" required>
            </div>

            <button type="submit">Register & Debug</button>
        </form>

        <div style="margin-top: 30px;">
            <h3>Debug Links</h3>
            <p><a href="/debug/registration" target="_blank">🔍 View System Debug Info (JSON)</a></p>
            <p><a href="/debug/documents" target="_blank">📄 Test Document Page Access (JSON)</a></p>
            <p><a href="/debug/middleware-test" target="_blank">🔐 Test Auth Middleware</a></p>
            <p><a href="/debug/test-redirect" target="_blank">🧪 Test Login and Redirect</a></p>
            <p><a href="{{ route('register.documents') }}" target="_blank">📋 Go to Document Upload</a></p>
            <p><a href="{{ route('register') }}">📝 Go to Normal Registration</a></p>
            <p><a href="{{ route('login') }}">🔑 Go to Login</a></p>

            <h4 style="margin-top: 20px;">Step-by-Step Test</h4>
            <ol style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <li><strong>First:</strong> Submit the registration form above</li>
                <li><strong>Then:</strong> Check if you see "Registration Success Debug Info" section</li>
                <li><strong>Next:</strong> Click the "Go to Document Upload" link in the success message</li>
                <li><strong>If it doesn't work:</strong> Use the debug links above to see what's happening</li>
            </ol>

            <div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0;">
                <strong>Common Issues:</strong>
                <ul>
                    <li>If you get "unauthenticated" - session/auth issue</li>
                    <li>If you get redirected to login - middleware blocking</li>
                    <li>If page loads but shows errors - Inertia/Vue issue</li>
                    <li>If nothing happens - JavaScript/redirect issue</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>