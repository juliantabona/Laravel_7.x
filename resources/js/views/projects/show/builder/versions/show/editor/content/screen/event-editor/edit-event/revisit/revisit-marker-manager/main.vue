<template>

    <!-- Marker selector -->
    <div class="d-flex">
        
        <span class="font-weight-bold text-dark mt-1 mr-2">Marker:</span>

        <Select
            v-model="event.event_data.revisit_type.marked_revisit.selected_marker"
            not-found-text="No markers found"  placeholder="Select marker" filterable>
            <Option v-for="(marker, index) in globalMarkers" :value="marker.text" :key="index">{{ marker.text }}</Option>
        </Select>

    </div>

</template>

<script>

    export default {
        props: { 
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
                
            }
        }
    };
  
</script>