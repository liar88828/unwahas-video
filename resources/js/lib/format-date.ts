/**
 * Format a Date, string, or timestamp to localized time or date string in 'id-ID' locale.
 * Default format is Indonesian time (HH:mm:ss).
 */
export function formatDate(
    date: Date | string | number = new Date(),
    options?: Intl.DateTimeFormatOptions,
    locale: string = 'id-ID',
): string {
    const d =
        typeof date === 'string' || typeof date === 'number'
            ? new Date(date)
            : date;
    const defaultOptions: Intl.DateTimeFormatOptions = {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        ...options,
    };

    return d.toLocaleTimeString(locale, defaultOptions);
}

export default formatDate;
