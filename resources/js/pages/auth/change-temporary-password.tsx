import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

export default function ChangeTemporaryPassword() {
    return <><Head title="Choose your password" />
        <Form action="/settings/password" method="put" resetOnError={['password', 'password_confirmation']}>
            {({ processing, errors }) => <div className="space-y-5">
                <div className="grid gap-2"><Label htmlFor="current_password">Temporary password</Label><PasswordInput id="current_password" name="current_password" autoComplete="current-password" required autoFocus /><InputError message={errors.current_password} /></div>
                <div className="grid gap-2"><Label htmlFor="password">New password</Label><PasswordInput id="password" name="password" autoComplete="new-password" minLength={8} required /><p className="text-sm text-muted-foreground">Use at least 8 characters. Do not reuse your temporary password.</p><InputError message={errors.password} /></div>
                <div className="grid gap-2"><Label htmlFor="password_confirmation">Confirm new password</Label><PasswordInput id="password_confirmation" name="password_confirmation" autoComplete="new-password" required /><InputError message={errors.password_confirmation} /></div>
                <Button className="w-full" disabled={processing}>Save password and continue</Button>
            </div>}
        </Form>
        <Form action="/logout" method="post"><Button variant="ghost" className="mt-4 w-full">Sign out</Button></Form>
    </>;
}

ChangeTemporaryPassword.layout = { title: 'Choose your own password', description: 'Your teacher provided a temporary password. Change it before continuing your lessons.' };
