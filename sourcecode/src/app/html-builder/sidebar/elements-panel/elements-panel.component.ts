import {
    ChangeDetectionStrategy,
    Component,
    ViewChild,
    ViewEncapsulation,
} from '@angular/core';
import {Elements} from '../../elements/elements.service';
import {MatAccordion} from '@angular/material/expansion';

@Component({
    selector: 'elements-panel',
    templateUrl: './elements-panel.component.html',
    styleUrls: ['./elements-panel.component.scss'],
    encapsulation: ViewEncapsulation.None,
    changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ElementsPanelComponent {
    @ViewChild(MatAccordion, {static: true}) matAccordion: MatAccordion;

    constructor(public elements: Elements) {}

    getElementsForCategory(name: string) {
        return this.elements.elements.filter(el => el.category === name);
    }
}
