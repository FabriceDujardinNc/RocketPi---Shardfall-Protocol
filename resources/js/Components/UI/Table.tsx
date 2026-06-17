import { type HTMLAttributes, type ThHTMLAttributes, type TdHTMLAttributes } from 'react';

export const Table = ({ className, ...props }: HTMLAttributes<HTMLTableElement>) => (
    <div className="rounded-lg border border-border-default bg-bg-elev1 overflow-x-auto">
        <table className={`w-full text-sm min-w-[640px] ${className ?? ''}`} {...props} />
    </div>
);

export const Thead = (props: HTMLAttributes<HTMLTableSectionElement>) => (
    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low" {...props} />
);

export const Tbody = (props: HTMLAttributes<HTMLTableSectionElement>) => <tbody {...props} />;

export const Tr = ({ className, ...props }: HTMLAttributes<HTMLTableRowElement>) => (
    <tr className={`border-t border-border-default hover:bg-bg-elev2/50 ${className ?? ''}`} {...props} />
);

export const Th = (props: ThHTMLAttributes<HTMLTableCellElement>) => (
    <th className="px-4 py-3 text-left font-display text-xs uppercase tracking-wide" {...props} />
);

export const Td = (props: TdHTMLAttributes<HTMLTableCellElement>) => (
    <td className="px-4 py-3 text-text-high" {...props} />
);

export default { Table, Thead, Tbody, Tr, Th, Td };
