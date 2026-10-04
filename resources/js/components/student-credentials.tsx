import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';

export default function StudentCredentials() {
    const page = usePage();
    const credentials = (page.props.flash as { credentials?: { name: string; username: string; password: string } } | undefined)?.credentials;
    const [open, setOpen] = useState(Boolean(credentials));
    useEffect(() => { if (credentials) setOpen(true); }, [credentials]);

    return <Dialog open={open} onOpenChange={setOpen}><DialogContent>
        <DialogHeader><DialogTitle>Student temporary password</DialogTitle><DialogDescription>Copy this password before closing. Share it privately with the student. They must choose a new password after signing in.</DialogDescription></DialogHeader>
        {credentials && <div className="space-y-4">
            <p className="font-semibold">{credentials.name}</p>
            <dl className="grid grid-cols-[100px_1fr] gap-2 rounded-xl bg-slate-950 p-4 text-white">
                <dt>Username</dt><dd><code>{credentials.username}</code></dd>
                <dt>Password</dt><dd><code className="text-amber-300">{credentials.password}</code></dd>
            </dl>
            <Button onClick={() => navigator.clipboard.writeText(`Username: ${credentials.username}\nTemporary password: ${credentials.password}`)}>Copy login details</Button>
        </div>}
    </DialogContent></Dialog>;
}
