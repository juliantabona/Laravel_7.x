<template>

    <div>

        <div class="bg-grey-light border mt-2 p-2">

            <!-- Reference Name Input -->
            <referenceNameInput 
                v-model="display.content.action.input_value.single_value_input.reference_name"
                :version="version" :screen="screen" :display="display"
                title="Reference:">
            </referenceNameInput>
            
        </div>
                
        <div class="bg-grey-light border mt-2 p-2">

            <screenAndDisplaySelector 
                :link="display.content.action.input_value.single_value_input.link" 
                :version="version" :screen="screen" :display="display">
            </screenAndDisplaySelector>

        </div>

    </div>
    
</template>

<script>

    import referenceNameInput from './../../../../referenceNameInput.vue';
    import screenAndDisplaySelector from './../../../../screenAndDisplaySelector.vue';

    export default {
        components: { referenceNameInput, screenAndDisplaySelector },
        props: { 
            screen: {
                type: Object,
                default: null
            },
            display: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
        }
    };
  
</script>