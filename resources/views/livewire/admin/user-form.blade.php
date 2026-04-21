<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                {{ $this->userId ? 'Edit User' : 'Create User' }}
            </h2>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-md shadow p-6">
            <form wire:submit="save" class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Full Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="John Doe"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Address <span class="text-red-600">*</span>
                    </label>
                    <input type="email" id="email" wire:model="email" placeholder="john@example.com"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password {{ !$this->userId ? '*' : '(leave empty to keep current)' }}
                    </label>
                    <input type="text" id="password" wire:model="password"
                        placeholder="{{ $this->userId ? '(leave empty to keep current)' : 'Enter password' }}"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        {{ !$this->userId ? 'required' : '' }}>
                    <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                    {{-- @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror    --}}
                </div>

                {{-- <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirm Password
                    </label>
                    <input type="password" id="password_confirmation" wire:model="password_confirmation"
                        placeholder="Confirm password"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div> --}}

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">
                        Role <span class="text-red-600">*</span>
                    </label>
                    <select id="role" wire:model.live="role"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                        <option value="">Select a role</option>
                        @foreach ($roles as $roleValue => $roleLabel)
                            <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Department (conditionally shown) -->
                @if ($this->shouldShowDepartmentField())
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700">
                            Department <span class="text-red-600">*</span>
                        </label>
                        <select id="department_id" wire:model="departmentId"
                            class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Select a department</option>
                            @foreach ($departments as $deptId => $deptName)
                                <option value="{{ $deptId }}">{{ $deptName }}</option>
                            @endforeach
                        </select>
                        @error('departmentId')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <!-- Manager (conditionally shown) -->
                @if ($this->shouldShowManagerField())
                    <div>
                        <label for="manager_id" class="block text-sm font-medium text-gray-700">
                            Manager
                        </label>
                        <select id="manager_id" wire:model="managerId"
                            class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">No manager assigned</option>
                            @foreach ($managers as $mgId => $mgName)
                                <option value="{{ $mgId }}">{{ $mgName }}</option>
                            @endforeach
                        </select>
                        @error('managerId')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex gap-4">
                    <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            {{ $this->userId ? 'Update User' : 'Create User' }}
                        </span>
                        <span wire:loading>
                            Saving...
                        </span>
                    </button>
                    <a href="{{ route('admin.users.index') }}" wire:navigate
                        class="inline-flex items-center px-6 py-2 bg-gray-300 text-gray-900 rounded-md hover:bg-gray-400">
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>