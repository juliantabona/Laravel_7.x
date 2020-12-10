<template>

    <!-- Repeat Screen Events -->
    <Row>

        <Col :span="24">

            <div class="border-bottom-dashed mt-3 pb-3 mb-3">
                            
                <!-- Show active state checkbox (Marks if this is active / inactive) -->
                <activeStateSelector v-model="screen.repeat.active" class="mb-2"></activeStateSelector>
                
            </div>

            <!-- Repeat Screen Type -->
            <div class="d-flex mb-3">

                <span class="font-weight-bold text-dark mt-1 mr-2">Repeat Type:</span>

                <Select v-model="screen.repeat.selected_type" style="width: 200px;">

                    <Option 
                        v-for="(repeatType, key) in repeatTypes"
                        :key="key" :value="repeatType.value" :label="repeatType.name">
                    </Option>

                </Select>

            </div>

            <!-- Repeat On Items Settings -->
            <repeatOnItemsSettings v-if="screen.repeat.selected_type == 'repeat_on_items'" :screen="screen" :version="version"></repeatOnItemsSettings>
            
            <!-- Repeat On Number Settings -->
            <repeatOnNumberSettings v-if="screen.repeat.selected_type == 'repeat_on_number'" :screen="screen" :version="version"></repeatOnNumberSettings>
        
        </Col>

    </Row>
    
</template>

<script>

    import repeatOnNumberSettings from './repeat-on-number/main.vue';
    import repeatOnItemsSettings from './repeat-on-items/main.vue';
    import activeStateSelector from './../activeStateSelector.vue';

    export default {
        components: { repeatOnNumberSettings, repeatOnItemsSettings, activeStateSelector },
        props: {
            screen: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                repeatTypes: [
                    {
                        name: 'Repeat on number',
                        value: 'repeat_on_number'
                    },
                    {
                        name: 'Repeat on items',
                        value: 'repeat_on_items'
                    },
                    {
                        name: 'Custom Repeat',
                        value: 'custom_repeat'
                    },
                ]
            }
        }
    };
  
</script>