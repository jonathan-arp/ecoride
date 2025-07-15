import React, { useState, useEffect } from "react";

export default function CarshareIndex() {
  console.log("CarshareIndex component is loading...");

  const [carshares, setCarshares] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    start_location: "",
    end_location: "",
    start_date: "",
    end_date: "",
    status: "",
    max_price: "",
    available_places: "",
  });
  const [showFilters, setShowFilters] = useState(false);

  useEffect(() => {
    fetchCarshares();
  }, [filters]);

  const fetchCarshares = async () => {
    console.log("Fetching carshares...");
    console.log("API URL:", window.CARSHARE_API_URL);

    setLoading(true);
    try {
      const params = new URLSearchParams();
      Object.keys(filters).forEach((key) => {
        if (filters[key]) {
          params.append(key, filters[key]);
        }
      });

      const apiUrl = `${window.CARSHARE_API_URL}?${params}`;
      console.log("Full API URL:", apiUrl);

      const response = await fetch(apiUrl);
      console.log("Response status:", response.status);

      const data = await response.json();
      console.log("Response data:", data);
      setCarshares(data);
    } catch (error) {
      console.error("Error fetching carshares:", error);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (field, value) => {
    setFilters((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const clearFilters = () => {
    setFilters({
      start_location: "",
      end_location: "",
      start_date: "",
      end_date: "",
      status: "",
      max_price: "",
      available_places: "",
    });
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("fr-FR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const getStatusBadgeClass = (status) => {
    switch (status) {
      case "available":
        return "bg-success";
      case "full":
        return "bg-warning";
      case "in_progress":
        return "bg-info";
      case "completed":
        return "bg-secondary";
      case "cancelled":
        return "bg-danger";
      default:
        return "bg-primary";
    }
  };

  const getStatusText = (status) => {
    switch (status) {
      case "available":
        return "Disponible";
      case "full":
        return "Complet";
      case "in_progress":
        return "En cours";
      case "completed":
        return "Terminé";
      case "cancelled":
        return "Annulé";
      default:
        return status;
    }
  };

  if (loading) {
    return (
      <div className="text-center py-5">
        <div className="spinner-border text-primary" role="status">
          <span className="visually-hidden">Chargement...</span>
        </div>
        <p className="mt-3">Chargement des trajets...</p>
      </div>
    );
  }

  return (
    <div>
      {/* Filter Section */}
      <div className="mb-4">
        <div className="d-flex justify-content-between align-items-center mb-3">
          <button
            className="btn btn-outline-primary"
            onClick={() => setShowFilters(!showFilters)}
          >
            <i className="fas fa-filter me-2"></i>
            {showFilters ? "Masquer les filtres" : "Afficher les filtres"}
          </button>
          <a href="/carshare/new" className="btn btn-success">
            <i className="fas fa-plus me-2"></i>
            Proposer un trajet
          </a>
        </div>

        {showFilters && (
          <div className="card">
            <div className="card-body">
              <div className="row">
                <div className="col-md-3">
                  <div className="form-group mb-3">
                    <label className="form-label">
                      <i className="fas fa-map-marker-alt me-1"></i>
                      Départ
                    </label>
                    <input
                      type="text"
                      className="form-control"
                      placeholder="Ville de départ"
                      value={filters.start_location}
                      onChange={(e) =>
                        handleFilterChange("start_location", e.target.value)
                      }
                    />
                  </div>
                </div>
                <div className="col-md-3">
                  <div className="form-group mb-3">
                    <label className="form-label">
                      <i className="fas fa-flag-checkered me-1"></i>
                      Arrivée
                    </label>
                    <input
                      type="text"
                      className="form-control"
                      placeholder="Ville d'arrivée"
                      value={filters.end_location}
                      onChange={(e) =>
                        handleFilterChange("end_location", e.target.value)
                      }
                    />
                  </div>
                </div>
                <div className="col-md-3">
                  <div className="form-group mb-3">
                    <label className="form-label">
                      <i className="fas fa-calendar me-1"></i>
                      Date de départ
                    </label>
                    <input
                      type="date"
                      className="form-control"
                      value={filters.start_date}
                      onChange={(e) =>
                        handleFilterChange("start_date", e.target.value)
                      }
                    />
                  </div>
                </div>
                <div className="col-md-3">
                  <div className="form-group mb-3">
                    <label className="form-label">
                      <i className="fas fa-calendar me-1"></i>
                      Date d'arrivée
                    </label>
                    <input
                      type="date"
                      className="form-control"
                      value={filters.end_date}
                      onChange={(e) =>
                        handleFilterChange("end_date", e.target.value)
                      }
                    />
                  </div>
                </div>
              </div>
              <div className="row">
                <div className="col-md-3">
                  <div className="form-group mb-3">
                    <label className="form-label">
                      <i className="fas fa-info-circle me-1"></i>
                      Statut
                    </label>
                    <select
                      className="form-control"
                      value={filters.status}
                      onChange={(e) =>
                        handleFilterChange("status", e.target.value)
                      }
                    >
                      <option value="">Tous les statuts</option>
                      <option value="available">Disponible</option>
                      <option value="full">Complet</option>
                      <option value="in_progress">En cours</option>
                    </select>
                  </div>
                </div>
                <div className="col-md-3">
                  <div className="form-group mb-3">
                    <label className="form-label">
                      <i className="fas fa-euro-sign me-1"></i>
                      Prix max
                    </label>
                    <input
                      type="number"
                      className="form-control"
                      placeholder="Prix maximum"
                      value={filters.max_price}
                      onChange={(e) =>
                        handleFilterChange("max_price", e.target.value)
                      }
                    />
                  </div>
                </div>
                <div className="col-md-3">
                  <div className="form-group mb-3">
                    <label className="form-label">
                      <i className="fas fa-users me-1"></i>
                      Places minimum
                    </label>
                    <input
                      type="number"
                      className="form-control"
                      placeholder="Nb places minimum"
                      value={filters.available_places}
                      onChange={(e) =>
                        handleFilterChange("available_places", e.target.value)
                      }
                    />
                  </div>
                </div>
                <div className="col-md-3">
                  <div className="form-group mb-3">
                    <label className="form-label">&nbsp;</label>
                    <div>
                      <button
                        className="btn btn-outline-secondary"
                        onClick={clearFilters}
                      >
                        <i className="fas fa-times me-2"></i>
                        Effacer
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Results Section */}
      <div className="mb-3">
        <small className="text-muted">
          {carshares.length} trajet{carshares.length > 1 ? "s" : ""} trouvé
          {carshares.length > 1 ? "s" : ""}
        </small>
      </div>

      {/* Carshares List */}
      {carshares.length === 0 ? (
        <div className="text-center py-5">
          <i className="fas fa-car fa-3x text-muted mb-3"></i>
          <h5 className="text-muted">Aucun trajet trouvé</h5>
          <p className="text-muted">
            {Object.values(filters).some((filter) => filter)
              ? "Essayez de modifier vos critères de recherche"
              : "Soyez le premier à proposer un trajet !"}
          </p>
          <a href="/carshare/new" className="btn btn-primary">
            <i className="fas fa-plus me-2"></i>Proposer un trajet
          </a>
        </div>
      ) : (
        <div className="row">
          {carshares.map((carshare) => (
            <div key={carshare.id} className="col-md-6 col-lg-4 mb-4">
              <div className="card h-100 shadow-sm">
                <div className="card-body">
                  <div className="d-flex justify-content-between align-items-start mb-3">
                    <h5 className="card-title mb-0">
                      <i className="fas fa-route me-2 text-primary"></i>
                      {carshare.start_location}
                    </h5>
                    <span
                      className={`badge ${getStatusBadgeClass(
                        carshare.status
                      )}`}
                    >
                      {getStatusText(carshare.status)}
                    </span>
                  </div>

                  <div className="mb-2">
                    <i className="fas fa-arrow-down me-2 text-muted"></i>
                    <span className="fw-bold">{carshare.end_location}</span>
                  </div>

                  <div className="mb-3">
                    <small className="text-muted">
                      <i className="fas fa-calendar me-1"></i>
                      {formatDate(carshare.start)}
                    </small>
                  </div>

                  <div className="row text-center mb-3">
                    <div className="col-4">
                      <div className="border-end">
                        <div className="fw-bold text-success">
                          {carshare.price}€
                        </div>
                        <small className="text-muted">Prix</small>
                      </div>
                    </div>
                    <div className="col-4">
                      <div className="border-end">
                        <div className="fw-bold text-info">
                          {carshare.place}
                        </div>
                        <small className="text-muted">Places</small>
                      </div>
                    </div>
                    <div className="col-4">
                      <div className="fw-bold text-primary">
                        {carshare.car?.brand} {carshare.car?.model}
                      </div>
                      <small className="text-muted">Véhicule</small>
                    </div>
                  </div>

                  <div className="text-center">
                    <small className="text-muted">
                      <i className="fas fa-user me-1"></i>
                      Conducteur: {carshare.driver?.firstname}{" "}
                      {carshare.driver?.lastname}
                    </small>
                  </div>
                </div>

                <div className="card-footer bg-white border-top-0">
                  <div className="d-grid">
                    <a
                      href={`/carshare/${carshare.id}`}
                      className="btn btn-outline-primary"
                    >
                      <i className="fas fa-eye me-2"></i>
                      Voir les détails
                    </a>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
