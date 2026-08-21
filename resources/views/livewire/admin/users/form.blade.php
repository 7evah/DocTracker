<div>
    <flux:modal name="user-form" class="max-w-xl">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:heading size="lg">
                {{ $this->isEditing()
                    ? __('admin.users.edit_heading', ['name' => $user->name])
                    : __('admin.users.create_heading') }}
            </flux:heading>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('common.labels.name')" required />
                <flux:input wire:model="email" :label="__('common.labels.email')" type="email" required />
                <flux:input wire:model="department" :label="__('common.labels.department')" />
                <flux:input wire:model="job_title" :label="__('Fonction')" />
                <flux:input wire:model="phone" :label="__('common.labels.phone')" type="tel" />

                <flux:select wire:model="status" :label="__('common.labels.status')">
                    @foreach ($statuses as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="password"
                    type="password"
                    :label="__('auth.login.password')"
                    :description="$this->isEditing()
                        ? __('admin.users.password_hint')
                        : __('admin.users.password_new_hint')"
                    field:class="sm:col-span-2"
                    viewable
                    :required="! $this->isEditing()"
                />
            </div>

            <flux:checkbox.group wire:model="roles" :label="__('admin.users.roles')">
                @foreach ($availableRoles as $role)
                    <flux:checkbox
                        :value="$role->value"
                        :label="$role->label()"
                        :description="$role->description()"
                        wire:key="role-{{ $role->value }}"
                    />
                @endforeach
            </flux:checkbox.group>

            <flux:error name="roles" />
            <flux:error name="status" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('common.actions.cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="check">
                    {{ __('common.actions.save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
