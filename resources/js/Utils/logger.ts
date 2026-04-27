type LogLevel = "debug" | "info" | "warn" | "error";

type LogContext = Record<string, unknown>;

const SENSITIVE_KEY_PATTERN = /^(password|passwd|token|secret|api_?key|access_?key|card_?number|cvv|cvc|credit_?card|authorization|cookie|session_?id)$/i;

const sanitizeContext = (context: LogContext): LogContext => {
    const sanitized: LogContext = {};
    for (const [key, value] of Object.entries(context)) {
        if (SENSITIVE_KEY_PATTERN.test(key)) {
            sanitized[key] = "[REDACTED]";
        } else if (typeof value === "object" && value !== null && !Array.isArray(value)) {
            sanitized[key] = sanitizeContext(value as LogContext);
        } else {
            sanitized[key] = value;
        }
    }
    return sanitized;
};

const isDev = import.meta.env.DEV;

const logToConsole = (level: LogLevel, message: string, details?: unknown) => {
    if (!isDev) {
        return;
    }

    const consoleFn = console[level] ?? console.log;
    if (details !== undefined) {
        consoleFn(message, details);
    } else {
        consoleFn(message);
    }
};

const normalizeError = (error: unknown, fallbackMessage: string): Error => {
    if (error instanceof Error) {
        return error;
    }
    if (typeof error === "string") {
        return new Error(error);
    }
    return new Error(fallbackMessage);
};

export const logger = {
    debug(message: string, context?: LogContext) {
        logToConsole("debug", message, context ? sanitizeContext(context) : undefined);
    },
    info(message: string, context?: LogContext) {
        logToConsole("info", message, context ? sanitizeContext(context) : undefined);
    },
    warn(message: string, context?: LogContext) {
        logToConsole("warn", message, context ? sanitizeContext(context) : undefined);
    },
    error(message: string, error?: unknown, context?: LogContext) {
        const normalized = normalizeError(error, message);
        logToConsole("error", message, {
            error: normalized,
            ...(context ? sanitizeContext(context) : {}),
        });
    },
    exception(error: unknown, context?: LogContext) {
        const normalized = normalizeError(error, "Unhandled error");
        logToConsole("error", normalized.message, {
            error: normalized,
            ...(context ? sanitizeContext(context) : {}),
        });
    },
};

let handlersAttached = false;

export const attachGlobalErrorHandlers = () => {
    if (handlersAttached || typeof window === "undefined") {
        return;
    }
    handlersAttached = true;

    window.addEventListener("error", (event) => {
        logger.exception(event.error ?? event.message, {
            source: event.filename,
            line: event.lineno,
            column: event.colno,
        });
    });

    window.addEventListener("unhandledrejection", (event) => {
        logger.exception(event.reason, { type: "unhandledrejection" });
    });
};
