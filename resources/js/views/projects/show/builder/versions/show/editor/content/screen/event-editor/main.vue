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
                :globalMarkers="globalMarkers"
                :builder="builder"
                :display="display"
                :screen="screen"
                :events="events"
                :event="event"
                :index="index">
            </singleEvent>
            
            <!-- No events message -->
            <Alert v-if="!eventsExist" type="info" class="mb-0" style="width:300px;" show-icon>No Events Found</Alert>

        </draggable>

        <div class="clearfix">

            <!-- Add Static Option Button -->
            <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                            class="float-right" :ripple="!eventsExist"
                            @click.native="handleAddEvent()">
                <span>Add Event</span>
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
                :builder="builder"
                @visibility="isOpenAddEventModal = $event">
            </createEventModal>
    
        </template>

    </div>

</template>

<script>

    import draggable from 'vuedraggable';
    import singleEvent from './single-event/main.vue';
    import createEventModal from './create-event/createEventModal.vue';
    import basicButton from './../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { draggable, singleEvent, createEventModal, basicButton },
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
            builder: {
                type: Object,
                default: () => {}
            },
            globalMarkers: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {
                isOpenAddEventModal: false,
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
            }
        },
        methods: {
            handleAddEvent(){
                this.isOpenAddEventModal = true;
            }
        }
    };
  
</script>