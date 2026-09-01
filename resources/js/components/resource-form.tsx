import { Form, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type SharedFieldProps = {
    name: string;
    label: string;
    required?: boolean;
    placeholder?: string;
};

export type ResourceFormField =
    | (SharedFieldProps & {
          type: 'text';
          defaultValue?: string;
          inputType?: 'text' | 'date';
      })
    | (SharedFieldProps & { type: 'textarea'; defaultValue?: string })
    | (SharedFieldProps & {
          type: 'select';
          defaultValue?: string;
          options: { value: string; label: string }[];
      });

type ResourceFormAction = {
    action: string;
    method: 'get' | 'post';
};

type ResourceFormProps = {
    form: ResourceFormAction;
    fields: ResourceFormField[];
    submitLabel: string;
    cancelHref: string;
};

/**
 * A field-config-driven form: create/edit screens for every entity pass
 * their own fields and get consistent labels, inline errors and a
 * submit/cancel row for free. Submission goes through Inertia's own `Form`
 * component (native `<form>` field collection), never a raw `fetch` call.
 */
export function ResourceForm({
    form,
    fields,
    submitLabel,
    cancelHref,
}: ResourceFormProps) {
    return (
        <Form
            {...form}
            options={{ preserveScroll: true }}
            className="space-y-6"
        >
            {({ processing, errors }) => {
                const fieldErrors = errors as Record<string, string>;

                return (
                    <>
                        {fields.map((field) => (
                            <div key={field.name} className="grid gap-2">
                                <Label htmlFor={field.name}>
                                    {field.label}
                                </Label>

                                {field.type === 'textarea' ? (
                                    <Textarea
                                        id={field.name}
                                        name={field.name}
                                        defaultValue={field.defaultValue}
                                        placeholder={field.placeholder}
                                        required={field.required}
                                        aria-invalid={Boolean(
                                            fieldErrors[field.name],
                                        )}
                                    />
                                ) : field.type === 'select' ? (
                                    <Select
                                        name={field.name}
                                        defaultValue={field.defaultValue}
                                    >
                                        <SelectTrigger
                                            id={field.name}
                                            className="w-full"
                                            aria-invalid={Boolean(
                                                fieldErrors[field.name],
                                            )}
                                        >
                                            <SelectValue
                                                placeholder={field.placeholder}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {field.options.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : (
                                    <Input
                                        id={field.name}
                                        name={field.name}
                                        type={field.inputType ?? 'text'}
                                        defaultValue={field.defaultValue}
                                        placeholder={field.placeholder}
                                        required={field.required}
                                        aria-invalid={Boolean(
                                            fieldErrors[field.name],
                                        )}
                                    />
                                )}

                                <InputError message={fieldErrors[field.name]} />
                            </div>
                        ))}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>{submitLabel}</Button>
                            <Link
                                href={cancelHref}
                                className="text-muted-foreground text-sm hover:underline"
                            >
                                Cancel
                            </Link>
                        </div>
                    </>
                );
            }}
        </Form>
    );
}
