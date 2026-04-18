module.exports = {
    root: true,
    env: {
        browser: true,
        node: true,
        es2022: true,
    },
    parserOptions: {
        ecmaVersion: "latest",
        sourceType: "module",
        ecmaFeatures: {
            jsx: true,
        },
    },
    settings: {
        react: {
            version: "detect",
        },
    },
    plugins: ["react", "react-hooks"],
    extends: ["eslint:recommended", "plugin:react/recommended", "plugin:react-hooks/recommended"],
    rules: {
        "react/no-array-index-key": "error",
        "react/react-in-jsx-scope": "off",
        "react/jsx-uses-react": "off",
        "react/prop-types": "off",
        "no-restricted-globals": [
            "error",
            {
                name: "fetch",
                message: 'Use "@/api" client instead of direct fetch',
            },
        ],
    },
    ignorePatterns: [
        "node_modules/",
        "vendor/",
        "public/",
        "storage/",
        "bootstrap/",
        "database/",
        "resources/views/",
        "resources/js/test/",
        "**/test/**",
    ],
    overrides: [
        {
            files: ["resources/js/api/client.ts"],
            rules: {
                "no-restricted-globals": "off",
            },
        },
        {
            files: ["**/*.{ts,tsx}"],
            parser: "@typescript-eslint/parser",
            parserOptions: {
                project: ["./tsconfig.json"],
                tsconfigRootDir: __dirname,
            },
            plugins: ["@typescript-eslint"],
            extends: [
                "plugin:@typescript-eslint/recommended",
                "plugin:@typescript-eslint/recommended-requiring-type-checking",
            ],
            rules: {
                "@typescript-eslint/no-explicit-any": "warn",
                "@typescript-eslint/no-unsafe-assignment": "off",
                "@typescript-eslint/no-unsafe-member-access": "off",
                "@typescript-eslint/no-unsafe-call": "off",
                "@typescript-eslint/no-unsafe-argument": "off",
                "@typescript-eslint/no-unsafe-return": "off",
                "@typescript-eslint/no-floating-promises": "warn",
                "@typescript-eslint/no-misused-promises": "warn",
                "@typescript-eslint/strict-boolean-expressions": "off",
                "@typescript-eslint/no-unused-vars": ["warn", { argsIgnorePattern: "^_" }],
                "no-empty-pattern": "warn",
                "no-param-reassign": ["error", { props: true }],
                "no-restricted-syntax": [
                    "error",
                    {
                        selector: "CallExpression[callee.property.name='sort']",
                        message: "Use toSorted() instead of mutating an array with sort().",
                    },
                    {
                        selector: "CallExpression[callee.property.name='reverse']",
                        message: "Use toReversed() or copy the array before reversing it.",
                    },
                    {
                        selector: "CallExpression[callee.property.name='splice']",
                        message: "Use toSpliced() or a copied array instead of splice().",
                    },
                    {
                        selector: "CallExpression[callee.property.name='copyWithin']",
                        message: "Copy the array before calling copyWithin().",
                    },
                    {
                        selector: "CallExpression[callee.property.name='fill']",
                        message: "Copy the array before calling fill().",
                    },
                ],
            },
        },
    ],
};
