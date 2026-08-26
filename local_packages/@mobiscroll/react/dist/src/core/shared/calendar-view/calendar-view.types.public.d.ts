import { CalendarViewBase } from './calendar-view';
import { ICalendarData, ICalendarViewHost } from './calendar-view.types';
export interface MbscResource {
    id: number | string;
    /** Specifies the name of the resource. */
    name?: string;
    /** Specifies the background color of the resource row or column. */
    background?: string;
    /** Specifies the displayed state of the child resource group. */
    collapsed?: boolean;
    /** Specifies the default event color of the resource. */
    color?: string;
    /** Specifies a css class for the resource row or column. */
    cssClass?: string;
    /** Depth of the resource in the resource tree. */
    depth?: number;
    /** Specifies whether the resource is fixed to the top. */
    fixed?: boolean;
    /** Will be true for resources with children. */
    isParent?: boolean;
    /** Child resources. */
    children?: MbscResource[];
    /** Disables event creation on specific resources by setting it to false. Defaults to true, if not specified. */
    eventCreation?: boolean;
    /** Specifies whether the events in this resource are movable across resources. */
    eventDragBetweenResources?: boolean;
    /** Specifies whether the events in this slots are movable across slots. */
    eventDragBetweenSlots?: boolean;
    /** Specifies whether the events in this resource are movable in time. */
    eventDragInTime?: boolean;
    /** Specifies whether the events in this resource can be overlapped */
    eventOverlap?: boolean;
    /** Specifies whether the events in this resource are resizable. */
    eventResize?: boolean;
    /** The original resource object. */
    original?: MbscResource;
    [x: string]: any;
}
export interface MbscSlot {
    /** This is an id that can be referenced in the events/invalids/colors data. */
    id: number | string;
    /** The name of the slot that will be displayed at the top of the slot column. */
    name?: string;
    color?: string;
    /** Specifies whether the event is movable across slots. */
    eventDragBetweenSlots?: boolean;
    [x: string]: any;
}
export interface MbscCalendarMarked extends ICalendarData {
    /** Color of the mark. */
    color?: string;
    /** CSS class for the mark. */
    markCssClass?: string;
    /** CSS class for the cell. */
    cellCssClass?: string;
}
export interface MbscCalendarColor extends ICalendarData {
    /** Background of the circle. */
    highlight?: string;
    /** Background of the cell. */
    background?: string;
    /** CSS class for the cell. */
    cellCssClass?: string;
}
export interface MbscCalendarLabel extends ICalendarData {
    /** Specifies if the label is all day or not. */
    allDay?: boolean;
    /** Background color of the label. */
    color?: string;
    /** CSS class for the cell. */
    cellCssClass?: string;
    /** Specifies whether the event is movable across resources. */
    dragBetweenResources?: boolean;
    /** Specifies whether the event is movable in time. */
    dragInTime?: boolean;
    /** Specifies if an event is editable or not. If false, drag & drop and resize is not allowed. */
    editable?: boolean;
    /** Specifies the order of the event in the event array. Has precedence over the default ordering rules. */
    order?: number;
    /** Specifies whether the event can be overlapped */
    overlap?: boolean;
    /** Specifies whether the event is resizable. */
    resize?: boolean;
    /** Text of the label */
    text?: string;
    /** Color of the label text. */
    textColor?: string;
    /** The title of the label. */
    title?: string;
    /** Tooltip for the label */
    tooltip?: string;
    /** @hidden */
    id?: string | number;
    /** @hidden */
    resource?: number | string | Array<number | string>;
}
export interface MbscCalendarEvent extends MbscCalendarLabel {
    /** Specifies if the event is all day or not. */
    allDay?: boolean;
    /** Defines a buffer time in minutes that will be displayed after the end of the event. */
    bufferAfter?: number;
    /** Defines a buffer time in minutes that will be displayed before the start of the event. */
    bufferBefore?: number;
    /** Specifies whether the event is movable across across slots. */
    dragBetweenSlots?: boolean;
    /** A unique id for the event. If not specified, the event will get a generated id. */
    id?: string | number;
    /** Resource or resources of the event. */
    resource?: number | string | Array<number | string>;
    /** Slot of the event */
    slot?: number | string;
    /** The title of the event. */
    title?: string;
    /** Timezone of the event */
    timezone?: string;
    /** Tooltip for the event */
    tooltip?: string;
    /** @hidden */
    background?: string;
}
export interface MbscCalendarEventData {
    allDay?: boolean;
    allDayText?: string;
    ariaLabel?: string;
    bufferAfter?: string;
    bufferBefore?: string;
    bufferStart?: Date;
    bufferEnd?: Date;
    color?: string;
    cssClass?: string;
    currentResource?: MbscResource;
    currentSlot?: MbscSlot;
    date: number;
    end?: string;
    endDate: Date;
    html?: any;
    id?: any;
    isMultiDay?: boolean;
    key?: string;
    lastDay?: string;
    layoutStart?: number;
    layoutEnd?: number;
    offset?: number;
    original?: MbscCalendarEvent;
    position?: any;
    resource?: number | string | Array<number | string>;
    showText?: boolean;
    slot?: number | string;
    track?: number;
    start?: string;
    startDate: Date;
    style?: any;
    title?: string;
    tooltip?: string;
    uid?: string | number;
}
export interface MbscCalendarHeaderOptions {
    calendar?: ICalendarViewHost;
    className?: string;
    view?: CalendarViewBase;
}
export interface MbscCalendarDayData {
    date: Date;
    endDate?: Date;
    events?: MbscCalendarEvent[];
    isActive?: boolean;
    selected?: boolean;
    resource?: number | string;
    startDate?: Date;
    weekNr?: number;
}
