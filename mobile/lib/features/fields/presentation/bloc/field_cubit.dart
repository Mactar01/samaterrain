import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/field_repository.dart';

abstract class FieldState {}
class FieldInitial extends FieldState {}
class FieldLoading extends FieldState {}
class FieldLoaded extends FieldState {
  final List<dynamic> fields;
  FieldLoaded(this.fields);
}
class FieldError extends FieldState {
  final String message;
  FieldError(this.message);
}

class FieldCubit extends Cubit<FieldState> {
  final FieldRepository repository;

  FieldCubit(this.repository) : super(FieldInitial());

  Future<void> fetchFields({String? search, String? city, String? type, double? lat, double? lng, int? radius}) async {
    emit(FieldLoading());
    try {
      final fields = await repository.getFields(
        search: search,
        city: city,
        type: type,
        lat: lat,
        lng: lng,
        radius: radius,
      );
      emit(FieldLoaded(fields));
    } catch (e) {
      emit(FieldError("Erreur lors du chargement des terrains."));
    }
  }

  Future<void> fetchOwnerFields() async {
    emit(FieldLoading());
    try {
      final fields = await repository.getOwnerFields();
      emit(FieldLoaded(fields));
    } catch (e) {
      emit(FieldError("Erreur lors du chargement de vos terrains."));
    }
  }
}
