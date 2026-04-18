import { Transition } from "@headlessui/react";
import { InertiaLinkProps, Link } from "@inertiajs/react";
import {
    Children,
    KeyboardEvent as ReactKeyboardEvent,
    cloneElement,
    createContext,
    Dispatch,
    isValidElement,
    PropsWithChildren,
    ReactElement,
    ReactNode,
    RefObject,
    SetStateAction,
    useContext,
    useId,
    useRef,
    useState,
} from "react";

const DropDownContext = createContext<{
    open: boolean;
    setOpen: Dispatch<SetStateAction<boolean>>;
    toggleOpen: () => void;
    triggerRef: RefObject<HTMLDivElement | null>;
    contentRef: RefObject<HTMLUListElement | null>;
    menuId: string;
}>({
    open: false,
    setOpen: () => {},
    toggleOpen: () => {},
    triggerRef: { current: null },
    contentRef: { current: null },
    menuId: "",
});

const Dropdown = ({ children }: PropsWithChildren) => {
    const [open, setOpen] = useState(false);
    const triggerRef = useRef<HTMLDivElement>(null);
    const contentRef = useRef<HTMLUListElement>(null);
    const menuId = useId();

    const toggleOpen = () => {
        setOpen((previousState) => !previousState);
    };

    return (
        <DropDownContext.Provider value={{ open, setOpen, toggleOpen, triggerRef, contentRef, menuId }}>
            <div className="relative">{children}</div>
        </DropDownContext.Provider>
    );
};

const Trigger = ({ children }: PropsWithChildren) => {
    const { open, setOpen, toggleOpen, triggerRef, contentRef, menuId } = useContext(DropDownContext);

    const focusTrigger = () => {
        const triggerElement = triggerRef.current;

        if (!triggerElement) {
            return;
        }

        const focusableTrigger = triggerElement.querySelector<HTMLElement>(
            "button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])",
        );

        (focusableTrigger ?? triggerElement).focus();
    };

    const getMenuItems = () => {
        return Array.from(contentRef.current?.querySelectorAll<HTMLElement>("[role='menuitem']") ?? []);
    };

    const focusMenuItemByIndex = (index: number) => {
        const menuItems = getMenuItems();

        if (menuItems.length === 0) {
            return;
        }

        const normalizedIndex = ((index % menuItems.length) + menuItems.length) % menuItems.length;
        menuItems[normalizedIndex]?.focus();
    };

    const handleTriggerKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>) => {
        if (event.key === "Escape" && open) {
            event.preventDefault();
            setOpen(false);
            focusTrigger();
            return;
        }

        if (event.key !== "ArrowDown" && event.key !== "ArrowUp") {
            return;
        }

        event.preventDefault();
        setOpen(true);

        requestAnimationFrame(() => {
            if (event.key === "ArrowDown") {
                focusMenuItemByIndex(0);
                return;
            }

            const menuItems = getMenuItems();
            focusMenuItemByIndex(menuItems.length - 1);
        });
    };

    return (
        <>
            <div
                ref={triggerRef}
                onClick={toggleOpen}
                onKeyDown={handleTriggerKeyDown}
                aria-haspopup="menu"
                aria-expanded={open}
                aria-controls={menuId}
            >
                {children}
            </div>

            {open && <div className="fixed inset-0 z-40" role="presentation" aria-hidden="true" onClick={() => setOpen(false)}></div>}
        </>
    );
};

const Content = ({
    align = "right",
    width = "48",
    contentClasses = "py-1 bg-white",
    children,
}: PropsWithChildren<{
    align?: "left" | "right";
    width?: "48";
    contentClasses?: string;
}>) => {
    const { open, setOpen, triggerRef, contentRef, menuId } = useContext(DropDownContext);

    let alignmentClasses = "origin-top";

    if (align === "left") {
        alignmentClasses = "ltr:origin-top-left rtl:origin-top-right start-0";
    } else if (align === "right") {
        alignmentClasses = "ltr:origin-top-right rtl:origin-top-left end-0";
    }

    let widthClasses = "";

    if (width === "48") {
        widthClasses = "w-48";
    }

    const focusTrigger = () => {
        const triggerElement = triggerRef.current;

        if (!triggerElement) {
            return;
        }

        const focusableTrigger = triggerElement.querySelector<HTMLElement>(
            "button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])",
        );

        (focusableTrigger ?? triggerElement).focus();
    };

    const getMenuItems = () => {
        return Array.from(contentRef.current?.querySelectorAll<HTMLElement>("[role='menuitem']") ?? []);
    };

    const handleMenuKeyDown = (event: ReactKeyboardEvent<HTMLUListElement>) => {
        if (event.key === "Escape") {
            event.preventDefault();
            setOpen(false);
            focusTrigger();
            return;
        }

        if (event.key !== "ArrowDown" && event.key !== "ArrowUp") {
            return;
        }

        event.preventDefault();

        const menuItems = getMenuItems();

        if (menuItems.length === 0) {
            return;
        }

        const currentIndex = menuItems.findIndex((item) => item === document.activeElement);
        const nextIndex =
            event.key === "ArrowDown"
                ? (currentIndex + 1 + menuItems.length) % menuItems.length
                : (currentIndex - 1 + menuItems.length) % menuItems.length;

        menuItems[nextIndex]?.focus();
    };

    const renderMenuItems = (nodes: ReactNode) => {
        return Children.map(nodes, (child) => {
            if (!child) {
                return null;
            }

            let menuItem = child;

            if (isValidElement<{ role?: string }>(child)) {
                const typedChild = child as ReactElement<{ role?: string }>;
                const role = typedChild.props.role ?? "menuitem";

                menuItem = cloneElement(typedChild, {
                    role,
                });
            }

            return <li role="none">{menuItem}</li>;
        });
    };

    return (
        <>
            <Transition
                show={open}
                enter=""
                enterFrom="opacity-0"
                enterTo="opacity-100"
                leave=""
                leaveFrom="opacity-100"
                leaveTo="opacity-0"
            >
                <div
                    className={`absolute z-50 mt-2 ${alignmentClasses} ${widthClasses}`}
                    onClick={() => setOpen(false)}
                >
                    <ul
                        id={menuId}
                        ref={contentRef}
                        role="menu"
                        aria-orientation="vertical"
                        className={`list-none m-0 border border-edge bg-white ` + contentClasses}
                        onKeyDown={handleMenuKeyDown}
                    >
                        {renderMenuItems(children)}
                    </ul>
                </div>
            </Transition>
        </>
    );
};

const DropdownLink = ({ className = "", children, ...props }: InertiaLinkProps) => {
    return (
        <Link
            {...props}
            role="menuitem"
            className={
                "block w-full px-4 py-2 text-start text-sm leading-5 text-ink-light hover:bg-surface focus:bg-surface focus:outline-none " +
                className
            }
        >
            {children}
        </Link>
    );
};

Dropdown.Trigger = Trigger;
Dropdown.Content = Content;
Dropdown.Link = DropdownLink;

export default Dropdown;
