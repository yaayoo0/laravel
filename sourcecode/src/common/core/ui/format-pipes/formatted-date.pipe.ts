import {Inject, LOCALE_ID, Pipe, PipeTransform} from '@angular/core';
import {DatePipe} from '@angular/common';
import {Settings} from '../../config/settings.service';

@Pipe({
    name: 'formattedDate'
})
export class FormattedDatePipe implements PipeTransform {
    private readonly format: string;
    private angularPipe: DatePipe;

    constructor(private settings: Settings, @Inject(LOCALE_ID) private _locale: string) {
        this.format = this.settings.get('dates.format', 'yyyy-MM-dd');
        this.angularPipe = new DatePipe(_locale);
    }

    transform(value: any, format?: string, timezone?: string, locale?: string): string | null {
        if (value && typeof value === 'string') {
            // 2 days ago
            if (value.endsWith('ago')) {
                return value;
            }
            // slash as separator for date will cause parse errors
            value = value.replace(/\//g, '-');
        }
        return this.angularPipe.transform(value, format || this.format, timezone, locale);
    }
}
