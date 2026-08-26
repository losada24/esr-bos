import { MbscCalendarDayData } from '../../../shared/calendar-view/calendar-view.types';
import { MbscSchedulerTimezone } from '../eventcalendar.types';
import { STBase } from '../shared/schedule-timeline-base';
import { ISTOptions, ISTState } from '../shared/schedule-timeline-base.types';
export interface ISchedulerOptions extends ISTOptions {
    groupBy?: 'date' | 'resource';
    timezones?: Array<MbscSchedulerTimezone | string> | undefined;
    type: 'month' | 'week' | 'day';
    renderDayContent?(args: MbscCalendarDayData): any;
    onWeekDayClick(arg: any): void;
}
export interface ISchedulerState extends ISTState {
    showShadow?: boolean;
}
/** @hidden */
export declare class SchedulerBase extends STBase<ISchedulerOptions, ISchedulerState> {
    _largeDayNames: boolean;
    _timeWidth?: {
        width: string;
    };
    _timezones?: MbscSchedulerTimezone[];
    protected _allDayCont?: HTMLElement | null;
    protected _timeCont?: HTMLElement | null;
    _onScroll: () => void;
    _setCont: (el: any) => void;
    _setTimeCont: (el: any) => void;
    _setAllDayCont: (el: any) => void;
    _setGridCont: (el: any) => void;
    _setHeaderCont: (el: any) => void;
    _setCursorTimeCont: (el: any) => void;
    protected _render(s: ISchedulerOptions, state: ISchedulerState): void;
}
