<template>
  <div>
    <div class="modal fade" id="add-edit-head" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <div class="modal-title modal-title-font" id="exampleModalLabel">{{ title }}</div>
          </div>
          <ValidationObserver v-slot="{ handleSubmit }">
            <form class="form-horizontal" id="form" @submit.prevent="handleSubmit(onSubmit)">
              <div class="modal-body">
                <div class="row">
                  <div class="col-6 col-md-6">
                    <ValidationProvider name="name" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="name">Head Name</label>
                        <input type="text" class="form-control" id="name" :class="{'error-border': errors[0]}" v-model="name" name="name" placeholder="Head Name" autocomplete="off">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <submit-form :name="buttonText"/>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </form>
          </ValidationObserver>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import {bus} from "../../app";
import {Common} from "../../mixins/common";

export default {
  mixins: [Common],
  data() {
    return {
      title: '',
      head_id: '',
      name: '',
      type: 'add',
      actionType: '',
      buttonShow: false,
      buttonText: ''
    }
  },
  computed: {},
  mounted() {
    $('#add-edit-head').on('hidden.bs.modal', () => {
      this.$emit('changeStatus')
    });
    bus.$on('add-edit-head', (row) => {
      if (row) {
        let instance = this;
        this.axiosGet('head/by-id/'+row.head_id,function(response) {
          var data = response.data;
          console.log(data)
          instance.title = 'Update Head';
          instance.buttonText = "Update";
          instance.head_id = data.head_id;
          instance.name = data.name;
          instance.buttonShow = true;
          instance.actionType = 'edit';
        },function(error){

        });
      } else {
        this.title = 'Add Head';
        this.buttonText = "Create";
        this.name = '';
        this.actionType = 'add'
      }
      $("#add-edit-head").modal("toggle");
    })
  },
  destroyed() {
    bus.$off('add-edit-head')
  },
  methods: {
    onSubmit() {
      this.$store.commit('submitButtonLoadingStatus', true);
      let url = '';
      if (this.actionType === 'add') url = 'head/create';
      else url = 'head/update/'+this.head_id
      this.axiosPost(url, {
        name: this.name,
      }, (response) => {
        this.successNoti(response.message);
        $("#add-edit-head").modal("toggle");
        bus.$emit('refresh-datatable');
        this.$store.commit('submitButtonLoadingStatus', false);
      }, (error) => {
        this.errorNoti(error);
        this.$store.commit('submitButtonLoadingStatus', false);
      })
    }
  }
}
</script>
