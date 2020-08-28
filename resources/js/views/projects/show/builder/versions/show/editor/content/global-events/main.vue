<template>

    <Card :bordered="false" :style="{ overflow: 'inherit' }">

        <!-- Main Heading -->  
        <Divider orientation="left">
            <span class="font-weight-bold text-dark">Global Events</span>
        </Divider>

        <!-- Display Event Explainer -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Events</span> to Create, Read, Update and Delete content using
            <span class="font-italic text-success font-weight-bold">CRUD API's</span> and
            <span class="font-italic text-success font-weight-bold">Validate</span>, 
            <span class="font-italic text-success font-weight-bold">Format</span> or 
            <span class="font-italic text-success font-weight-bold">Store</span> 
            content including the users input among other dynamic content.
        </Alert>

        <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
            
            <eventsManager :events="version.builder.global_events" :globalMarkers="globalMarkers" 
                           :version="version" btnText="Add Global Event"
                           :usingGlobalEventManager="true">
            </eventsManager>

        </div>
    
    </Card>

</template>

<script>

    import basicButton from './../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { basicButton },
        props: {
            version: {
                type: Object,
                default: null
            },
            globalMarkers: {
                type: Array,
                default: () => []
            }
        },
        data(){

            return {
                
            }
        },
        computed: {
            globalEventsExist(){
                return this.version.builder.global_events.length ? true : false;
            },
            addButtonType(){
                return this.globalEventsExist ? 'primary' : 'success';
            }
        },
        methods: {
            
        }
    }
</script>
