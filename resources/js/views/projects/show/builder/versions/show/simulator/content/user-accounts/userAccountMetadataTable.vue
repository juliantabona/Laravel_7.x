<template>
    
    <div>

        <Table row-key="id" :columns="columns" :data="data" border></Table>

    </div>

</template>

<script>

    export default {
        props: {
            metadata: {
                type: Object,
                default: null
            }
        },
        data () {
            return {
                columnData : {
                    name: 'John Brown',
                    work: {
                        active: true,
                        phone: '3990867',
                        address: {
                            plot: '1234 Bridge'
                        }
                    }
                },
                columns: [
                    {
                        title: 'Field',
                        key: 'field',
                        tree: true
                    },
                    {
                        title: 'Value',
                        render: (h, params) => {
                            return this.renderTag(h, params);
                        }
                    }
                ],
                data: [],
                booleanTypes: [
                    {
                        name: 'True',
                        value: 'true',
                    },
                    {
                        name: 'False',
                        value: 'false',
                    }
                ],
            }
        },
        methods: {
            structureMetadataForTable(data, metadataKeys, firstIteration = false){

                /** This method converts the given metadata into a proper format to load on the 
                 *  iview table component. This example below shows the before and after format
                 *  of the metadata data supplied.
                 * 
                 *  BEFORE CONVERSION
                 * 
                 *  {
                 *      name: 'John Brown',
                 *      work: {
                 *          active: true,
                 *          phone: '3990867',
                 *          address: {
                 *              plot: '1234 Bridge'
                 *          }
                 *      }
                 *  }
                 * 
                 *  AFTER CONVERSION
                 * 
                 *  [
                 *      {
                 *          field: 'name',
                 *          value: 'John Brown',
                 *      },
                 *      {
                 *          field: 'work',
                 *          value: null,    //  Because the value is an Object the value must be null
                 *          children: [
                 *              {
                 *                  field: 'active',
                 *                  value: 'true'       //  Return selector here
                 *              },
                 *              {
                 *                  field: 'phone',
                 *                  value: '3990867'    //  Return input here
                 *              },
                 *              {
                 *                  field: 'address',
                 *                  value: null,        //  Because the value is an Object the value must be null
                 *                  children: [
                 *                      {
                 *                          field: 'plot',
                 *                          value: '1234 Bridge'    //  Return input here
                 *                      }
                 *                  ]
                 *              }
                 *          }
                 *      }
                 *  ]
                 */

                //  If this is the first iteration
                if( firstIteration == true ){

                    //  If we do not have any metadata fields and values
                    if( data == null || data.length == 0 ){

                        //  Return nothing to show
                        return [];

                    }

                }

                /** Example Structure of "data"
                 *  
                 *   {
                 *      name: 'John Brown',
                 *      work: {
                 *           active: true,
                 *           phone: '3990867',
                 *           address: {
                 *               plot: '1234 Bridge'
                 *           }
                 *      }
                 *   } 
                 */

                var objectKeys = Object.keys(data);
                var objectValues = Object.values(data);

                var results = [];

                for (let x = 0; x < objectKeys.length; x++) {

                    var field = objectKeys[x];
                    var value = objectValues[x];

                    var id = x+'_'+field;

                    metadataKeys.push(field);

                    /** Then  
                     * 
                     *  field = 'name', value = 'John Brown' or
                     * 
                     *  field = 'work', value = {...}
                     * 
                     *  e.t.c. 
                     */

                    //  If the provided value is an Object
                    if( (typeof value == 'object' ) ){

                        results.push({
                            id: '1_'+id,
                            field: field,
                            value: null,
                            metadataKeys: Object.values(metadataKeys),
                            children: this.structureMetadataForTable(value, Object.values(metadataKeys), false)
                        });

                    //  If the provided value is not an Object
                    }else{

                        results.push({
                            id: '2_'+id,
                            field: field,
                            value: value,
                            metadataKeys: Object.values(metadataKeys)
                        });

                    }

                    metadataKeys.pop();
                    
                }

                return results;
                
            },
            renderTag(h, params){

                var value = params.row.value;

                //  If this row value 
                if( ((value || {}).children || {}).length == 0 ){

                    return null;

                }else{

                    //  If the value is a string, undefined or null
                    if( (typeof value == 'string') ){
                        
                        return this.renderInput(h, params, value);

                    //  If the value is a boolean
                    }else if( (typeof value == 'boolean') ){
                        
                        return this.renderSelect(h, params, value);

                    }else{

                        return null;

                    }

                }

            },
            renderInput(h, params, value){
                
                const self = this;
                const tableParams = params;

                return h('Input', {
                    props:{
                        type: 'text',
                        value: value
                    },
                    on: {
                        input: function (event) {
                            self.updatedField(event, tableParams);
                        }
                    }
                });

            },
            renderSelect(h, params, value){
                
                const self = this;
                const tableParams = params;

                var options = this.booleanTypes.map( (booleanType, index) => {
                    return h('Option', {
                        props:{
                            value: booleanType.value,
                            key: index
                        }
                    }, booleanType.name);
                });

                return h('Select', {
                    props:{
                        class: ['w-100'],
                        size: 'small',
                        value: value.toString()
                    },
                    on: {
                        onChange: function (event) {
                            console.log('event 1');
                            console.log(event);
                            //self.updatedField(event, tableParams);
                        },
                        change: function (event) {
                            console.log('event 2');
                            console.log(event);
                            //self.updatedField(event, tableParams);
                        },
                        input: function (event) {
                            console.log('event 3');
                            console.log(event);
                            //self.updatedField(event, tableParams);
                        }
                    },
                    nativeOn:{
                        input: function (event) {
                            console.log('event 4');
                            console.log(event);
                            //self.updatedField(event, tableParams);
                        },
                        change: function (event) {
                            console.log('event 5');
                            console.log(event);
                            //self.updatedField(event, tableParams);
                        }
                    }
                }, options);

            },
            updatedField(value, params){

                var metadata = this.metadata;

                for (let x = 0; x < params.row.metadataKeys.length; x++) {
                    
                    var index = params.row.metadataKeys[x];

                    if( x !== (params.row.metadataKeys.length - 1) ){

                        metadata = metadata[ index ];

                    }else{

                        this.$set(metadata, index, value);
                    }
                    
                }
            },
        },
        created(){
            this.data = this.structureMetadataForTable(this.metadata, [], true);
        }
    }
</script>