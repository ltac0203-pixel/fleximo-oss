import { Component, ComponentType, ErrorInfo, ReactNode } from "react";
import { logger } from "@/Utils/logger";

export interface FallbackProps {
    error: Error;
    resetError: () => void;
}

interface ErrorBoundaryProps {
    children: ReactNode;
    fallback?: ComponentType<FallbackProps>;
    onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface ErrorBoundaryState {
    hasError: boolean;
    error: Error | null;
}

export default class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
    constructor(props: ErrorBoundaryProps) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error: Error): ErrorBoundaryState {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
        logger.exception(error, {
            componentStack: errorInfo.componentStack ?? undefined,
        });
        this.props.onError?.(error, errorInfo);
    }

    componentDidMount(): void {
        window.addEventListener("popstate", this.handleNavigation);
    }

    componentWillUnmount(): void {
        window.removeEventListener("popstate", this.handleNavigation);
    }

    private handleNavigation = (): void => {
        if (this.state.hasError) {
            this.resetError();
        }
    };

    private resetError = (): void => {
        this.setState({ hasError: false, error: null });
    };

    render(): ReactNode {
        if (this.state.hasError && this.state.error) {
            const FallbackComponent = this.props.fallback;
            if (FallbackComponent) {
                return <FallbackComponent error={this.state.error} resetError={this.resetError} />;
            }
            return null;
        }

        return this.props.children;
    }
}
