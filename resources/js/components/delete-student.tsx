import { Form } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

export default function DeleteStudent({ student }: { student: { id: number; name: string; username: string } }) {
    const [open, setOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);

    return <Dialog open={open} onOpenChange={(value) => { if (!deleting) setOpen(value); }}>
        <DialogTrigger asChild><Button variant="destructive"><Trash2 /> Delete student</Button></DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete this student?</DialogTitle>
                <DialogDescription>Are you sure you want to permanently delete {student.name} (@{student.username})? Their account, saved activities, lesson progress, submissions, and related feedback will be removed. This cannot be undone.</DialogDescription>
            </DialogHeader>
            <Form action={`/students/${student.id}`} method="delete" onStart={() => setDeleting(true)} onFinish={() => setDeleting(false)}>
                {({ processing, errors }) => <div className="space-y-4">
                    <input type="hidden" name="confirmed" value="1" />
                    <InputError message={errors.confirmed} />
                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" autoFocus disabled={processing} onClick={() => setOpen(false)}>Cancel</Button>
                        <Button type="submit" variant="destructive" disabled={processing}>{processing ? 'Deleting…' : 'Yes, delete student'}</Button>
                    </div>
                </div>}
            </Form>
        </DialogContent>
    </Dialog>;
}
