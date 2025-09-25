<template>
  <div>
    <div class="modal fade" id="add-edit-student" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <div class="modal-title modal-title-font" id="exampleModalLabel">{{ title }}</div>
          </div>
          <ValidationObserver v-slot="{ handleSubmit }">
            <form class="form-horizontal" id="form" @submit.prevent="handleSubmit(onSubmit)">
              <div class="modal-body">
                <div class="row">
                  <div class="col-12 col-md-12">
                    <ValidationProvider name="name" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="name">Session Name</label>
                        <input type="text" class="form-control" id="name" :class="{'error-border': errors[0]}"
                               v-model="name" name="name" placeholder="Ex: 2023-2024" autocomplete="off">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-12">
                    <ValidationProvider name="from year" mode="eager" rules="required|min:4|max:4" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="fromYear">From Year</label>
                        <input type="text" class="form-control" :class="{'error-border': errors[0]}" id="fromYear"
                               v-model="fromYear" name="fromYear" placeholder="Ex: 2023">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-12">
                    <ValidationProvider name="to year" mode="eager" rules="required|min:4|max:4" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="toYear">To Year</label>
                        <input type="text" class="form-control" id="toYear" :class="{'error-border': errors[0]}"
                               v-model="toYear" name="toYear" placeholder="Ex: 2024">
                        <span class="error-message"> {{ errors[0] }}</span>
                      </div>
                    </ValidationProvider>
                  </div>
                  <div class="col-12 col-md-12">
                    <ValidationProvider name="status" mode="eager" rules="required" v-slot="{ errors }">
                      <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" v-model="status" class="form-control" id="status">
                          <option value="Y">Active</option>
                          <option value="N">Deactivate</option>
                        </select>
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
import {mapGetters} from "vuex";

export default {
  mixins: [Common],
  // components: {Multiselect},
  data() {
    return {
      title: '',
      sessionId: '',
      name: '',
      fromYear: '',
      toYear: '',
      status: '',
      type: 'add',
      actionType: '',
      buttonShow: false,
      buttonText: ''
    }
  },
  computed: {},
  created() {
    // this.getData();
  },
  mounted() {
    $('#add-edit-student').on('hidden.bs.modal', () => {
      this.$emit('changeStatus')
    });
    bus.$on('add-edit-student', (row) => {
      if (row) {
        let instance = this;
        this.axiosGet('students/by-id/'+row.session_id,function(response) {
          var data = response.data;
          instance.title = 'Update Session';
          instance.buttonText = "Update";
          instance.sessionId = data.session_id;
          instance.name = data.name;
          instance.fromYear = data.from_year;
          instance.toYear = data.to_year;
          instance.status = data.status;
          instance.buttonShow = true;
          instance.actionType = 'edit';
        },function(error){

        });
      } else {
        this.title = 'Add Student';
        this.buttonText = "Create";
        this.firstName = '';
        this.lastName = '';
        this.email = '';
        this.mobile = '';
        this.dateOfBirth = '';
        this.nid = '';
        this.address = '';
        this.nationality = '';
        this.sessionId = '';
        this.status = 'Y';
        this.actionType = 'add'
      }
      $("#add-edit-sessions").modal("toggle");
      // $(".error-message").html("");
    })
  },
  destroyed() {
    bus.$off('add-edit-sessions')
  },
  methods: {
    onSubmit() {
      this.$store.commit('submitButtonLoadingStatus', true);
      let url = '';
      if (this.actionType === 'add') url = 'students/create';
      else url = 'students/update/'+this.sessionId
      this.axiosPost(url, {
        firstName: this.firstName,
        lastName: this.lastName,
        email: this.email,
        mobile: this.mobile,
        dateOfBirth: this.dateOfBirth,
        nid: this.nid,
        address: this.address,
        nationality: this.nationality,
        sessionId: this.sessionId,
        status: this.status
      }, (response) => {
        this.successNoti(response.message);
        $("#add-edit-student").modal("toggle");
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

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
