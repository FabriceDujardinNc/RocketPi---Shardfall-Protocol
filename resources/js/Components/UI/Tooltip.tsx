import * as RTooltip from '@radix-ui/react-tooltip';
import { type PropsWithChildren, type ReactNode } from 'react';

interface Props extends PropsWithChildren {
    content: ReactNode;
    side?: 'top' | 'right' | 'bottom' | 'left';
}

export default function Tooltip({ content, side = 'top', children }: Props) {
    return (
        <RTooltip.Provider delayDuration={300}>
            <RTooltip.Root>
                <RTooltip.Trigger asChild>{children}</RTooltip.Trigger>
                <RTooltip.Portal>
                    <RTooltip.Content
                        side={side}
                        sideOffset={6}
                        className="z-tooltip rounded-md bg-bg-elev3 border border-border-default px-3 py-1.5 font-body text-xs text-text-high shadow-el2"
                    >
                        {content}
                        <RTooltip.Arrow className="fill-bg-elev3" />
                    </RTooltip.Content>
                </RTooltip.Portal>
            </RTooltip.Root>
        </RTooltip.Provider>
    );
}
