import * as RTabs from '@radix-ui/react-tabs';
import { type PropsWithChildren } from 'react';

export const Root = ({ children, ...props }: PropsWithChildren<RTabs.TabsProps>) => (
    <RTabs.Root {...props}>{children}</RTabs.Root>
);

export const List = ({ children }: PropsWithChildren) => (
    <RTabs.List className="inline-flex items-center gap-1 rounded-md bg-bg-elev1 border border-border-default p-1">
        {children}
    </RTabs.List>
);

export const Trigger = ({ value, children }: PropsWithChildren<{ value: string }>) => (
    <RTabs.Trigger
        value={value}
        className="px-4 h-8 rounded-sm font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high data-[state=active]:bg-shard-500/15 data-[state=active]:text-shard-400 transition-colors duration-fast"
    >
        {children}
    </RTabs.Trigger>
);

export const Content = ({ value, children }: PropsWithChildren<{ value: string }>) => (
    <RTabs.Content value={value} className="mt-4 focus:outline-none">
        {children}
    </RTabs.Content>
);

export default { Root, List, Trigger, Content };
