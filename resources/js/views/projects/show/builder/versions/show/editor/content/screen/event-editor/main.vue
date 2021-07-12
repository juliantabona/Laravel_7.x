<template>

    <div>

        <!-- Event List & Dragger  -->
        <draggable
            :list="events"
            @start="drag=true"
            @end="drag=false"
            :options="{
                group:'events',
                handle:'.dragger-handle',
                draggable:'.single-draggable-item',
            }">

            <!-- Single Event  -->
            <singleEvent v-for="(event, index) in events" :key="event.id+'_'+index"
                :usingGlobalEventManager="usingGlobalEventManager"
                :usingImportEventManager="usingImportEventManager"
                @import="handleImport($event)"
                :globalMarkers="globalMarkers"
                :version="version"
                :display="display"
                :screen="screen"
                :events="events"
                :event="event"
                :index="index">
            </singleEvent>

            <!-- No events message -->
            <Alert v-if="!eventsExist" type="info" class="mb-0" style="width:300px;" show-icon>No Events Found</Alert>

        </draggable>

        <div v-if="!usingImportEventManager" class="clearfix">

            <!-- Add Static Option Button -->
            <basicButton :type="addButtonType" size="small" icon="ios-add" :showIcon="true"
                            class="float-right" :ripple="!eventsExist" iconDirection="left"
                            @click.native="handleAddEvent()">
                <span>{{ btnText }}</span>
            </basicButton>

            <!-- Add Static Option Button -->
            <basicButton
                v-if="!usingGlobalEventManager" class="float-right mr-2" type="primary" size="small"
                icon="ios-cloud-download-outline" :showIcon="true" iconDirection="left"
                @click.native="handleImportEvent()">
                <span>Import Event</span>
            </basicButton>

            <!-- Add Static Option Button -->
            <basicButton v-if="canPasteEvent" type="primary" size="small" class="float-right mr-2"
                         iconDirection="left" @click.native="pasteEvent()">
                <span>Paste</span>
            </basicButton>

        </div>

        <!--
            MODAL TO ADD EVENT
        -->
        <template v-if="isOpenAddEventModal">

            <createEventModal
                :events="events"
                :screen="screen"
                :display="display"
                :version="version"
                @visibility="isOpenAddEventModal = $event">
            </createEventModal>

        </template>

        <!--
            MODAL TO IMPORT GLOBAL EVENT
        -->
        <template v-if="isOpenImportGlobalEventModal">

            <importGlobalEventModal
                :events="events"
                :version="version"
                @import="handleImport($event)"
                @visibility="isOpenImportGlobalEventModal = $event">
            </importGlobalEventModal>

        </template>

    </div>

</template>

<script>

    import draggable from 'vuedraggable';
    import singleEvent from './single-event/main.vue';
    import createEventModal from './create-event/createEventModal.vue';
    import importGlobalEventModal from './import-event/importGlobalEventModal.vue';
    import basicButton from './../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { draggable, singleEvent, createEventModal, importGlobalEventModal, basicButton },
        props: {
            events: {
                type: Array,
                default: () => []
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            version: {
                type: Object,
                default: () => {}
            },
            globalMarkers: {
                type: Array,
                default: () => []
            },
            btnText: {
                type: String,
                default: 'Add Event'
            },
            usingGlobalEventManager: {
                type: Boolean,
                default: false
            },
            usingImportEventManager: {
                type: Boolean,
                default: false
            }

        },
        data(){
            return {
                isOpenAddEventModal: false,
                isOpenImportGlobalEventModal: false
            }
        },
        computed: {
            totalEvents(){
                return this.events.length;
            },
            eventsExist(){
                return this.totalEvents ? true : false;
            },
            addButtonType(){
                return this.eventsExist ? 'default' : 'success';
            },
            canPasteEvent(){

                //  Return the stored event
                var event = window.localStorage.getItem('event');

                //  Convert String to Object
                event = event ? JSON.parse(event) : null;

                return event ? true : false;

            }
        },
        methods: {
            handleImportEvent(){
                this.isOpenImportGlobalEventModal = true;
            },
            handleImport(event){

                if( this.isOpenImportGlobalEventModal == false ){

                    this.$emit('import', event);

                }else{

                    this.events.push(event);

                    //  Event imported success message
                    this.$Message.success({
                        content: 'Event imported!',
                        duration: 6
                    });

                }
            },
            handleAddEvent(){
                this.isOpenAddEventModal = true;
            },
            pasteEvent(){

                //  Return the stored event
                var event = window.localStorage.getItem('event');

                //  Convert String to Object
                event = event ? JSON.parse(event) : null;

                //  If we have an event
                if(event){

                    //  Add event to rest of events
                    this.events.push(event);

                    this.$Message.success({
                        content: 'Event pasted!',
                        duration: 6
                    });

                }

            }
        }
    };

</script>
