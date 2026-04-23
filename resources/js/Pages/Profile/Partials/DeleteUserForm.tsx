import Button from "@/Components/UI/Button";
import { useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function LogoutForm({ className = "" }: { className?: string }) {
    const { post, processing } = useForm({});

    const handleLogout: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("logout"));
    };

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <div className="h-px w-8 bg-sky-400 mb-3"></div>
                <h2 className="text-lg font-medium text-slate-900">ログアウト</h2>

                <p className="mt-1 text-sm text-slate-600">現在のセッションからログアウトします。</p>
            </header>

            <form onSubmit={handleLogout}>
                <Button variant="danger" disabled={processing} isBusy={processing}>
                    ログアウト
                </Button>
            </form>
        </section>
    );
}
