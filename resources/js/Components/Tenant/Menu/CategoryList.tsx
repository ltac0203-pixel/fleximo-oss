import { MenuCategory } from "@/types";
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragEndEvent,
} from "@dnd-kit/core";
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import AvailabilityBadge from "./AvailabilityBadge";

interface CategoryListProps {
    categories: MenuCategory[];
    onEdit?: (category: MenuCategory) => void;
    onDelete?: (category: MenuCategory) => void;
    onReorder?: (orderedIds: number[]) => void;
}

interface SortableCategoryItemProps {
    category: MenuCategory;
    onEdit?: (category: MenuCategory) => void;
    onDelete?: (category: MenuCategory) => void;
    canManage: boolean;
}

function SortableCategoryItem({ category, onEdit, onDelete, canManage }: SortableCategoryItemProps) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: category.id,
        disabled: !canManage,
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`flex items-center justify-between p-4 bg-white border ${isDragging ? "opacity-50" : ""}`}
        >
            <div className="flex items-center gap-4">
                {canManage && (
                    <button
                        {...attributes}
                        {...listeners}
                        className="cursor-grab active:cursor-grabbing text-muted-light hover:text-ink-light"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 8h16M4 16h16" />
                        </svg>
                    </button>
                )}
                <div>
                    <span className="font-medium text-ink">{category.name}</span>
                </div>
                <AvailabilityBadge isActive={category.is_active} />
            </div>

            {canManage && (
                <div className="flex gap-2">
                    {onEdit && (
                        <button
                            onClick={() => onEdit(category)}
                            className="text-primary-dark hover:text-primary-dark text-sm font-medium"
                        >
                            編集
                        </button>
                    )}
                    {onDelete && (
                        <button
                            onClick={() => onDelete(category)}
                            className="text-red-600 hover:text-red-900 text-sm font-medium"
                        >
                            削除
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

export default function CategoryList({ categories, onEdit, onDelete, onReorder }: CategoryListProps) {
    const canManage = !!(onEdit || onDelete || onReorder);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleDragEnd = (event: DragEndEvent) => {
        if (!onReorder) return;

        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = categories.findIndex((c) => c.id === active.id);
            const newIndex = categories.findIndex((c) => c.id === over.id);
            const newCategories = arrayMove(categories, oldIndex, newIndex);
            onReorder(newCategories.map((c) => c.id));
        }
    };

    if (categories.length === 0) {
        return <div className="text-center py-8 text-muted">カテゴリがまだありません</div>;
    }

    return (
        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
            <SortableContext items={categories.map((c) => c.id)} strategy={verticalListSortingStrategy}>
                <div className="space-y-2">
                    {categories.map((category) => (
                        <SortableCategoryItem
                            key={category.id}
                            category={category}
                            onEdit={onEdit}
                            onDelete={onDelete}
                            canManage={canManage}
                        />
                    ))}
                </div>
            </SortableContext>
        </DndContext>
    );
}
