<template>

    <div>

        <!-- Event Collection Instruction -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Event Collection</span> to group events that
            work together to archieve a similar goal or functionality
        </Alert>

        <!-- Collection Events -->
        <div class="mb-2">

            <span class="d-block font-weight-bold text-dark">Collection Events</span>

            <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">

                <eventsManager :events="event.event_data.events" :globalMarkers="globalMarkers" :version="version" :screen="screen" :display="display"></eventsManager>

            </div>

        </div>

    </div>

</template>

<script>

    export default {
        props:{
            event: {
                type: Object,
                default:() => {}
            },
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
            }
        },
        data(){
            return{

            }
        }
    }
</script>
