<template>

    <!-- Repeat Events -->
    <div>

        <!-- Repeat Event Explainer -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Events</span> to Create, Read, Update and Delete content using
            <span class="font-italic text-success font-weight-bold">CRUD API's</span> and
            <span class="font-italic text-success font-weight-bold">Validate</span>, 
            <span class="font-italic text-success font-weight-bold">Format</span> or 
            <span class="font-italic text-success font-weight-bold">Store</span> 
            content including the users input among other dynamic content.
        </Alert>

        <!-- Before Repeat Events -->
        <div class="mb-2">

            <span class="d-block font-weight-bold text-dark">Before Repeat</span>

            <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
                
                <eventsManager :events="screen.repeat.events.before_repeat" :globalMarkers="globalMarkers" :version="version" :screen="screen"></eventsManager>

            </div>

        </div>

        <!-- After Repeat Events -->
        <div class="mb-2">

            <span class="d-block font-weight-bold text-dark">After Repeat</span>

            <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
                
                <eventsManager :events="screen.repeat.events.after_repeat" :globalMarkers="globalMarkers" :version="version" :screen="screen"></eventsManager>

            </div>

        </div>

    </div>
    
</template>

<script>

    export default {
        props: { 
            screen:{
                type: Object,
                default: () => {}
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
            return {
                
            }
        }
    };
  
</script>