import { cva } from 'class-variance-authority';

export { default as Alert } from './Alert.vue';
export { default as AlertTitle } from './AlertTitle.vue';
export { default as AlertDescription } from './AlertDescription.vue';

export const alertVariants = cva(
  'relative w-full rounded-lg border p-4 [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg]:text-foreground [&>svg~*]:pl-7',
  {
    variants: {
      variant: {
        default: 'bg-card text-card-foreground border-border',
        warning: 'border-amber-300 text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/30 [&>svg]:text-amber-600',
        destructive: 'border-destructive/50 text-destructive bg-destructive/5 [&>svg]:text-destructive',
      },
    },
    defaultVariants: { variant: 'default' },
  },
);
