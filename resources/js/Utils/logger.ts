import * as Sentry from "@sentry/react";

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
const sentryDsn = import.meta.env.VITE_SENTRY_DSN;
const sentryEnvironment = import.meta.env.VITE_SENTRY_ENVIRONMENT ?? import.meta.env.MODE;
const sentryRelease = import.meta.env.VITE_SENTRY_RELEASE;
const isTrackingEnabled = !isDev && Boolean(sentryDsn);

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

let sentryInitialized = false;

export const initializeSentry = () => {
    if (!isTrackingEnabled || sentryInitialized) {
        return;
    }

    Sentry.init({
        dsn: sentryDsn,
        environment: sentryEnvironment,
        release: sentryRelease,
        integrations: [
            Sentry.browserTracingIntegration(),
            Sentry.replayIntegration({
                maskAllText: true,
                maskAllInputs: true,
                blockAllMedia: false,
            }),
        ],
        tracesSampleRate: 0.2,
        tracePropagationTargets: [/^\//],
        replaysSessionSampleRate: 0.1,
        replaysOnErrorSampleRate: 1.0,
        beforeSend(rawEvent) {
            const sanitized = { ...rawEvent };
            if (sanitized.request?.data && typeof sanitized.request.data === "object") {
                sanitized.request = {
                    ...sanitized.request,
                    data: sanitizeContext(sanitized.request.data as LogContext),
                };
            }
            return sanitized;
        },
    });

    sentryInitialized = true;
};

const captureException = (error: unknown, context?: LogContext) => {
    if (!isTrackingEnabled) {
        return;
    }

    initializeSentry();

    Sentry.withScope((scope) => {
        if (context) {
            scope.setContext("context", sanitizeContext(context));
        }
        scope.setLevel("error");
        Sentry.captureException(error);
    });
};

export const logger = {
    debug(message: string, context?: LogContext) {
        logToConsole("debug", message, context);
    },
    info(message: string, context?: LogContext) {
        logToConsole("info", message, context);
    },
    warn(message: string, context?: LogContext) {
        logToConsole("warn", message, context);
    },
    error(message: string, error?: unknown, context?: LogContext) {
        const normalized = normalizeError(error, message);
        logToConsole("error", message, normalized);
        captureException(normalized, context);
    },
    exception(error: unknown, context?: LogContext) {
        const normalized = normalizeError(error, "Unhandled error");
        logToConsole("error", normalized.message, normalized);
        captureException(normalized, context);
    },
};

let handlersAttached = false;

export const attachGlobalErrorHandlers = () => {
    if (handlersAttached || typeof window === "undefined") {
        return;
    }
    handlersAttached = true;

    initializeSentry();

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
