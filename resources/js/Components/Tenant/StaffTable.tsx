import { Staff } from "@/types";
import { useState, useMemo } from "react";

type SortableStaffField = "name" | "email" | "role" | "is_active";

interface StaffTableProps {
    staff: Staff[];
    onEdit?: (staff: Staff) => void;
    onDelete?: (staff: Staff) => void;
}

function SortIndicator({ active, direction }: { active: boolean; direction: "asc" | "desc" }) {
    if (!active) {
        return (
            <svg className="ml-1 inline h-3 w-3 text-muted-light" viewBox="0 0 10 14" fill="currentColor">
                <path d="M5 0L9 5H1L5 0Z" />
                <path d="M5 14L1 9H9L5 14Z" />
            </svg>
        );
    }
    return (
        <svg className="ml-1 inline h-3 w-3 text-ink-light" viewBox="0 0 10 8" fill="currentColor">
            {direction === "asc" ? <path d="M5 0L10 8H0L5 0Z" /> : <path d="M5 8L0 0H10L5 8Z" />}
        </svg>
    );
}

export default function StaffTable({ staff, onEdit, onDelete }: StaffTableProps) {
    const [sortBy, setSortBy] = useState<SortableStaffField | null>(null);
    const [sortDir, setSortDir] = useState<"asc" | "desc">("asc");

    const handleSort = (field: SortableStaffField) => {
        if (sortBy === field) {
            setSortDir((prev) => (prev === "asc" ? "desc" : "asc"));
        } else {
            setSortBy(field);
            setSortDir("asc");
        }
    };

    const sortedStaff = useMemo(() => {
        if (!sortBy) return staff;
        return staff.toSorted((a, b) => {
            let cmp = 0;
            switch (sortBy) {
                case "name":
                case "email":
                    cmp = (a[sortBy] ?? "").localeCompare(b[sortBy] ?? "", "ja");
                    break;
                case "role": {
                    const roleOrder = { admin: 0, staff: 1 };
                    const aVal = a.role !== null ? (roleOrder[a.role] ?? 2) : 2;
                    const bVal = b.role !== null ? (roleOrder[b.role] ?? 2) : 2;
                    cmp = aVal - bVal;
                    break;
                }
                case "is_active":
                    cmp = Number(b.is_active ? 1 : 0) - Number(a.is_active ? 1 : 0);
                    break;
            }
            return sortDir === "asc" ? cmp : -cmp;
        });
    }, [staff, sortBy, sortDir]);

    const canManage = onEdit || onDelete;
    if (staff.length === 0) {
        return (
            <div className="text-center py-12">
                <p className="text-muted">スタッフが登録されていません</p>
            </div>
        );
    }

    return (
        <>
            {/* モバイルカードビュー を明示し、実装意図の誤読を防ぐ。 */}
            <div className="md:hidden space-y-3">
                {sortedStaff.map((member) => (
                    <div key={member.id} className="border border-edge rounded-lg p-4 bg-white">
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-sm font-medium text-ink">{member.name}</span>
                            <div className="flex items-center gap-2">
                                <span
                                    className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${
                                        member.role === "admin"
                                            ? "bg-sky-100 text-sky-800"
                                            : "bg-surface-dim text-ink-light"
                                    }`}
                                >
                                    {member.role === "admin" ? "管理者" : "スタッフ"}
                                </span>
                                <span
                                    className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${
                                        member.is_active ? "bg-green-100 text-green-800" : "bg-surface-dim text-ink"
                                    }`}
                                >
                                    {member.is_active ? "有効" : "無効"}
                                </span>
                            </div>
                        </div>
                        <div className="space-y-1 text-sm text-muted">
                            <p>{member.email}</p>
                            {member.phone && <p>{member.phone}</p>}
                        </div>
                        {canManage && (
                            <div className="mt-3 pt-3 border-t border-edge flex gap-4">
                                {onEdit && (
                                    <button
                                        onClick={() => onEdit(member)}
                                        className="text-sm text-sky-600 hover:text-sky-800"
                                    >
                                        編集
                                    </button>
                                )}
                                {onDelete && (
                                    <button
                                        onClick={() => onDelete(member)}
                                        className="text-sm text-red-600 hover:text-red-900"
                                    >
                                        削除
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {/* デスクトップテーブルビュー を明示し、実装意図の誤読を防ぐ。 */}
            <div className="hidden md:block overflow-x-auto border border-edge">
                <table className="min-w-full divide-y divide-edge">
                    <thead className="bg-surface">
                        <tr>
                            <th
                                className="px-6 py-3 text-left text-xs font-medium text-ink-light uppercase tracking-wider cursor-pointer select-none hover:bg-surface-dim"
                                onClick={() => handleSort("name")}
                            >
                                <span className="inline-flex items-center">
                                    名前
                                    <SortIndicator active={sortBy === "name"} direction={sortDir} />
                                </span>
                            </th>
                            <th
                                className="px-6 py-3 text-left text-xs font-medium text-ink-light uppercase tracking-wider cursor-pointer select-none hover:bg-surface-dim"
                                onClick={() => handleSort("email")}
                            >
                                <span className="inline-flex items-center">
                                    メールアドレス
                                    <SortIndicator active={sortBy === "email"} direction={sortDir} />
                                </span>
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-ink-light uppercase tracking-wider">
                                電話番号
                            </th>
                            <th
                                className="px-6 py-3 text-left text-xs font-medium text-ink-light uppercase tracking-wider cursor-pointer select-none hover:bg-surface-dim"
                                onClick={() => handleSort("role")}
                            >
                                <span className="inline-flex items-center">
                                    権限
                                    <SortIndicator active={sortBy === "role"} direction={sortDir} />
                                </span>
                            </th>
                            <th
                                className="px-6 py-3 text-left text-xs font-medium text-ink-light uppercase tracking-wider cursor-pointer select-none hover:bg-surface-dim"
                                onClick={() => handleSort("is_active")}
                            >
                                <span className="inline-flex items-center">
                                    ステータス
                                    <SortIndicator active={sortBy === "is_active"} direction={sortDir} />
                                </span>
                            </th>
                            {canManage && (
                                <th className="px-6 py-3 text-right text-xs font-medium text-ink-light uppercase tracking-wider">
                                    操作
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-edge">
                        {sortedStaff.map((member) => (
                            <tr key={member.id}>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-ink">
                                    {member.name}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-muted">{member.email}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-muted">
                                    {member.phone || "-"}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span
                                        className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                            member.role === "admin"
                                                ? "bg-sky-100 text-sky-800"
                                                : "bg-surface-dim text-ink-light"
                                        }`}
                                    >
                                        {member.role === "admin" ? "管理者" : "スタッフ"}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span
                                        className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                            member.is_active
                                                ? "bg-green-100 text-green-800"
                                                : "bg-surface-dim text-ink"
                                        }`}
                                    >
                                        {member.is_active ? "有効" : "無効"}
                                    </span>
                                </td>
                                {canManage && (
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        {onEdit && (
                                            <button
                                                onClick={() => onEdit(member)}
                                                className="text-sky-600 hover:text-sky-800 mr-4"
                                            >
                                                編集
                                            </button>
                                        )}
                                        {onDelete && (
                                            <button
                                                onClick={() => onDelete(member)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                削除
                                            </button>
                                        )}
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
