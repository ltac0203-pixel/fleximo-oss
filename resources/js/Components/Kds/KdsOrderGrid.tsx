import { KdsOrder, KdsStatusUpdateTarget } from "@/types";
import KdsGridCard from "./KdsGridCard";

interface KdsOrderGridProps {
    orders: KdsOrder[];
    onStatusUpdate: (orderId: number, newStatus: KdsStatusUpdateTarget) => void;
}

export default function KdsOrderGrid({ orders, onStatusUpdate }: KdsOrderGridProps) {
    if (orders.length === 0) {
        return (
            <div className="flex items-center justify-center h-48 text-muted-light text-lg">
                表示する注文がありません
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {orders.map((order) => (
                <KdsGridCard key={order.id} order={order} onStatusUpdate={onStatusUpdate} />
            ))}
        </div>
    );
}
