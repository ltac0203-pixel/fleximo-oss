import { CustomerMenuOptionGroup } from "@/types";
import OptionGroupSelector from "./OptionGroupSelector";

interface OptionSelectorProps {
    groups: CustomerMenuOptionGroup[];
    selectedOptionsByGroup: Record<number, number[]>;
    onChange: (groupId: number, optionIds: number[]) => void;
}

export default function OptionSelector({ groups, selectedOptionsByGroup, onChange }: OptionSelectorProps) {
    if (groups.length === 0) return null;

    return (
        <div className="mb-4">
            {groups.map((group) => (
                <OptionGroupSelector
                    key={group.id}
                    group={group}
                    selectedOptions={selectedOptionsByGroup[group.id] || []}
                    onChange={(optionIds) => onChange(group.id, optionIds)}
                />
            ))}
        </div>
    );
}
