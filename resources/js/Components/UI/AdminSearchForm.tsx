import { FormEventHandler } from "react";

interface AdminSearchFormProps {
    name?: string;
    defaultValue?: string;
    placeholder: string;
    onSubmit: (query: string) => void;
    submitLabel?: string;
    className?: string;
}

export default function AdminSearchForm({
    name = "search",
    defaultValue,
    placeholder,
    onSubmit,
    submitLabel = "検索",
    className = "",
}: AdminSearchFormProps) {
    const handleSubmit: FormEventHandler<HTMLFormElement> = (e) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);
        const value = (formData.get(name) as string | null) ?? "";
        onSubmit(value);
    };

    return (
        <form onSubmit={handleSubmit} className={`flex gap-2 ${className}`.trim()}>
            <input
                type="text"
                name={name}
                defaultValue={defaultValue ?? ""}
                placeholder={placeholder}
                className="rounded-md border border-edge-strong px-3 py-2 text-sm focus:border-primary focus:outline-none"
            />
            <button
                type="submit"
                className="bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
            >
                {submitLabel}
            </button>
        </form>
    );
}
