export interface StorefrontProduct {
    id: number;
    name: string;
    slug: string;
    type: 'product' | 'digital' | 'service';
    category: string;
    tagline: string | null;
    description: string;
    features: string[] | null;
    image_url: string | null;
    price: string | number;
    compare_at_price: string | number | null;
    billing_period: string | null;
    stock: number | null;
    reserved_stock?: number;
    available_stock?: number | null;
    is_featured: boolean;
    is_active: boolean;
}

export interface CartItem {
    product: StorefrontProduct;
    quantity: number;
    line_total: number;
}

export interface OrderConfirmation {
    order_number: string;
    customer_name: string;
    customer_email: string;
    total: string | number;
    items: {
        id: number;
        name: string;
        quantity: number;
        line_total: string | number;
    }[];
}
