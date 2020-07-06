<template>

    <!-- Select Screen Link -->
    <screenAndDisplaySelector 
        :link="event.event_data.revisit_type.screen_revisit.link"
        :builder="builder" :screen="screen" :display="display" :showDisplays="false">
    </screenAndDisplaySelector>

</template>

<script>
    import screenAndDisplaySelector from './../../../../screenAndDisplaySelector.vue';

    export default {
        components: { screenAndDisplaySelector },
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
        },
        data(){
            return {
                
            }
        }
    };
  
</script>